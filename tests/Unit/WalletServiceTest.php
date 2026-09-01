<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_rejects_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 20000]);
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 35000]);

        $service = new WalletService();

        $this->expectException(InsufficientBalanceException::class);
        $service->pay($user, $order, 35000);

        $this->assertEquals(20000, $user->wallet->fresh()->balance);
    }

    public function test_pay_deducts_balance_and_records_transaction(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 50000]);
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 35000]);

        $service = new WalletService();
        $transaction = $service->pay($user, $order, 35000);

        $this->assertEquals(15000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'payment',
            'amount' => 35000,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'balance_after' => 15000,
        ]);
    }

    public function test_refund_adds_balance_correctly_and_records_transaction(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 5000]);
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 35000]);

        $service = new WalletService();
        $transaction = $service->refund($user, $order, 35000);

        $this->assertEquals(40000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $transaction->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'refund',
            'amount' => 35000,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'balance_after' => 40000,
        ]);
    }

    public function test_get_balance_and_history_returns_correct_balance_and_ordered_transactions(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 75000]);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $service = new WalletService();
        $service->pay($user, $order, 25000);
        $service->refund($user, $order, 10000);

        $result = $service->getBalanceAndHistory($user);

        $this->assertEquals(60000, $result['balance']);
        $this->assertEquals(2, $result['transactions']->total());
        // Urutan terbaru duluan: refund (transaksi kedua) harus muncul lebih dulu
        $this->assertEquals('refund', $result['transactions']->items()[0]->type);
    }
}
