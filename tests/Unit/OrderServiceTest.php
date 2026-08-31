<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cafe;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): OrderService
    {
        return new OrderService(new WalletService());
    }

    public function test_calculates_total_correctly_from_base_price_and_option_extra_prices(): void
    {
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create([
            'cafe_id' => $cafe->id,
            'base_price' => 20000,
            'is_available' => true,
        ]);
        $sizeOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'size',
            'option_value' => 'Large',
            'extra_price' => 5000,
        ]);
        $sweetnessOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'sweetness',
            'option_value' => '50%',
            'extra_price' => 0,
        ]);

        $service = $this->makeService();

        $result = $service->calculateTotal([
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'option_ids' => [$sizeOption->id, $sweetnessOption->id],
            ],
        ], $cafe->id);


        $this->assertEquals(50000, $result['total_amount']);
        $this->assertEquals(25000, $result['items'][0]['unit_price']);
        $this->assertEquals(50000, $result['items'][0]['subtotal']);
    }

    public function test_sums_total_across_multiple_items(): void
    {
        $cafe = Cafe::factory()->create();
        $productA = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 20000, 'is_available' => true]);
        $productB = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 15000, 'is_available' => true]);

        $service = $this->makeService();

        $result = $service->calculateTotal([
            ['product_id' => $productA->id, 'quantity' => 2, 'option_ids' => []],
            ['product_id' => $productB->id, 'quantity' => 1, 'option_ids' => []],
        ], $cafe->id);


        $this->assertEquals(55000, $result['total_amount']);
    }

    public function test_rejects_unavailable_product(): void
    {
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'is_available' => false]);

        $service = $this->makeService();

        $this->expectException(ProductUnavailableException::class);
        $service->calculateTotal([
            ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => []],
        ], $cafe->id);
    }

    public function test_rejects_product_from_different_cafe(): void
    {
        $cafeA = Cafe::factory()->create();
        $cafeB = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafeB->id, 'is_available' => true]);

        $service = $this->makeService();

        $this->expectException(ProductUnavailableException::class);
        $service->calculateTotal([
            ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => []],
        ], $cafeA->id);
    }

    public function test_rejects_option_id_that_does_not_belong_to_product(): void
    {
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'is_available' => true]);
        $otherProduct = Product::factory()->create(['cafe_id' => $cafe->id]);
        $foreignOption = ProductOption::factory()->create(['product_id' => $otherProduct->id]);

        $service = $this->makeService();

        $this->expectException(ProductUnavailableException::class);
        $service->calculateTotal([
            ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => [$foreignOption->id]],
        ], $cafe->id);
    }

    public function test_create_persists_order_with_snapshot_and_deducts_wallet(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 100000]);
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 20000, 'is_available' => true]);
        $option = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'size',
            'option_value' => 'Large',
            'extra_price' => 5000,
        ]);

        $service = $this->makeService();

        $order = $service->create($user, $cafe->id, [
            ['product_id' => $product->id, 'quantity' => 2, 'option_ids' => [$option->id]],
        ], 'less sugar please');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
            'status' => 'pending',
            'total_amount' => 50000, // (20000+5000) x 2
            'notes' => 'less sugar please',
        ]);

        $this->assertCount(1, $order->items);
        $this->assertEquals(25000, $order->items->first()->unit_price);
        $this->assertCount(1, $order->items->first()->options);
        $this->assertEquals('Large', $order->items->first()->options->first()->option_value);

        $this->assertEquals(50000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'payment',
            'amount' => 50000,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_create_rolls_back_completely_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 10000]);
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 20000, 'is_available' => true]);

        $service = $this->makeService();

        $this->expectException(InsufficientBalanceException::class);

        try {
            $service->create($user, $cafe->id, [
                ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => []],
            ]);
        } finally {
            $this->assertEquals(0, Order::count());
            $this->assertEquals(10000, $user->wallet->fresh()->balance);
        }
    }

    public function test_create_rejects_unavailable_product_and_persists_nothing(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 100000]);
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'is_available' => false]);

        $service = $this->makeService();

        $this->expectException(ProductUnavailableException::class);

        try {
            $service->create($user, $cafe->id, [
                ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => []],
            ]);
        } finally {
            $this->assertEquals(0, Order::count());
            $this->assertEquals(100000, $user->wallet->fresh()->balance);
        }
    }

    public function test_update_status_allows_pending_to_preparing(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'pending']);
        $service = $this->makeService();

        $updated = $service->updateStatus($order, 'preparing');

        $this->assertEquals('preparing', $updated->status);
    }

    public function test_update_status_allows_preparing_to_finished(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'preparing']);
        $service = $this->makeService();

        $updated = $service->updateStatus($order, 'finished');

        $this->assertEquals('finished', $updated->status);
    }

    public function test_update_status_rejects_pending_to_finished_directly(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'pending']);
        $service = $this->makeService();

        $this->expectException(\App\Exceptions\InvalidOrderStatusTransitionException::class);
        $service->updateStatus($order, 'finished');
    }

    public function test_update_status_rejects_transition_from_finished(): void
    {
        $order = \App\Models\Order::factory()->create(['status' => 'finished']);
        $service = $this->makeService();

        $this->expectException(\App\Exceptions\InvalidOrderStatusTransitionException::class);
        $service->updateStatus($order, 'preparing');
    }

    public function test_cancel_refunds_wallet_and_sets_status_cancelled(): void
    {
        $user = User::factory()->create();
        Wallet::where('user_id', $user->id)->update(['balance' => 15000]);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 35000,
        ]);

        $service = $this->makeService();
        $cancelled = $service->cancel($order);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals(50000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'refund',
            'amount' => 35000,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'balance_after' => 50000,
        ]);
    }

    public function test_cancel_works_from_preparing_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'preparing',
            'total_amount' => 20000,
        ]);

        $service = $this->makeService();
        $cancelled = $service->cancel($order);

        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_cancel_rejects_finished_order(): void
    {
        $order = Order::factory()->create(['status' => 'finished']);
        $service = $this->makeService();

        $this->expectException(\App\Exceptions\InvalidOrderStatusTransitionException::class);
        $service->cancel($order);
    }

    public function test_cancel_rejects_already_cancelled_order_to_prevent_double_refund(): void
    {
        $user = User::factory()->create();
        $initialBalance = $user->wallet->balance;
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

        $service = $this->makeService();

        try {
            $service->cancel($order);
            $this->fail('Expected InvalidOrderStatusTransitionException was not thrown.');
        } catch (\App\Exceptions\InvalidOrderStatusTransitionException $e) {
            $this->assertEquals($initialBalance, $user->wallet->fresh()->balance);
        }
    }
}
