<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Cafe;
use App\Models\CafeOperatingHour;
use App\Models\CafePhoto;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseFoundationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Semua tabel yang wajib ada sesuai spec.md §2, hasil kerja Modul 02.
     */
    private array $foundationTables = [
        'cafes',
        'cafe_photos',
        'cafe_operating_hours',
        'products',
        'product_options',
        'bank_accounts',
        'wallets',
        'wallet_transactions',
        'topup_requests',
        'orders',
        'order_items',
        'order_item_options',
    ];

    public function test_migrate_fresh_seed_runs_without_error_and_all_tables_exist(): void
    {
        // RefreshDatabase sudah migrate:fresh secara implisit untuk test ini,
        // tapi kita jalankan eksplisit dengan --seed untuk memastikan seeder juga tidak error.
        Artisan::call('db:seed', ['--class' => 'BankAccountSeeder']);

        foreach ($this->foundationTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak ditemukan.");
        }

        // Pastikan seeder BankAccountSeeder benar-benar jalan
        $this->assertGreaterThanOrEqual(2, BankAccount::count());
    }

    public function test_basic_model_relations_are_accessible(): void
    {
        $user = User::factory()->create();

        $cafe = Cafe::create([
            'name' => 'Test Cafe',
            'city' => 'Yogyakarta',
            'address' => 'Jl. Test No. 1',
        ]);

        $photo = $cafe->photos()->create([
            'photo_path' => 'test.jpg',
            'sort_order' => 1,
        ]);

        $hour = $cafe->operatingHours()->create([
            'day_of_week' => 0,
            'open_time' => '08:00',
            'close_time' => '20:00',
        ]);

        $product = $cafe->products()->create([
            'category' => 'coffee',
            'name' => 'Espresso',
            'base_price' => 20000,
        ]);

        $option = $product->options()->create([
            'option_type' => 'size',
            'option_value' => 'Large',
            'extra_price' => 5000,
        ]);

        $bankAccount = BankAccount::first() ?? BankAccount::create([
            'bank_name' => 'BCA',
            'account_number' => '111',
            'account_holder' => 'Test',
        ]);

        $wallet = $user->wallet()->create(['balance' => 0]);

        $transaction = $wallet->transactions()->create([
            'type' => 'topup',
            'amount' => 50000,
            'balance_after' => 50000,
        ]);

        $topup = TopupRequest::create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount' => 50000,
            'proof_image_path' => 'proof.jpg',
            'status' => 'pending',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
            'total_amount' => 25000,
        ]);

        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);

        $orderItemOption = $orderItem->options()->create([
            'option_type' => 'size',
            'option_value' => 'Large',
            'extra_price' => 5000,
        ]);

        // Assertion relasi belongsTo & hasMany, dari kedua arah
        $this->assertTrue($photo->cafe->is($cafe));
        $this->assertTrue($hour->cafe->is($cafe));
        $this->assertTrue($cafe->photos->contains($photo));
        $this->assertTrue($cafe->operatingHours->contains($hour));

        $this->assertTrue($product->cafe->is($cafe));
        $this->assertTrue($cafe->products->contains($product));

        $this->assertTrue($option->product->is($product));
        $this->assertTrue($product->options->contains($option));

        $this->assertTrue($wallet->user->is($user));
        $this->assertTrue($user->wallet->is($wallet));

        $this->assertTrue($transaction->wallet->is($wallet));
        $this->assertTrue($wallet->transactions->contains($transaction));

        $this->assertTrue($topup->user->is($user));
        $this->assertTrue($topup->bankAccount->is($bankAccount));
        $this->assertNull($topup->verifier);

        $this->assertTrue($order->user->is($user));
        $this->assertTrue($order->cafe->is($cafe));
        $this->assertTrue($user->orders->contains($order));
        $this->assertTrue($cafe->orders->contains($order));

        $this->assertTrue($orderItem->order->is($order));
        $this->assertTrue($orderItem->product->is($product));
        $this->assertTrue($order->items->contains($orderItem));

        $this->assertTrue($orderItemOption->orderItem->is($orderItem));
        $this->assertTrue($orderItem->options->contains($orderItemOption));

        // Verifikasi ulang aturan snapshot dari perspektif model (bukan hanya skema)
        $this->assertFalse(method_exists($orderItemOption, 'productOption'));
    }

    public function test_users_cafe_id_foreign_key_still_enforced(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['cafe_id' => 99999]);
    }
}
