<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\User;
use App\Models\Cafe;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_user_and_cafe(): void
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'cafe_id' => $cafe->id]);

        $this->assertTrue($order->user->is($user));
        $this->assertTrue($order->cafe->is($cafe));
    }

    public function test_order_has_many_items(): void
    {
        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertTrue($order->items()->whereKey($item->id)->exists());
        $this->assertTrue($item->order->is($order));
    }

    public function test_order_item_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $item = OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertTrue($item->product->is($product));
    }

    public function test_order_item_has_many_options(): void
    {
        $item = OrderItem::factory()->create();
        $option = OrderItemOption::factory()->create(['order_item_id' => $item->id]);

        $this->assertTrue($item->options->contains($option));
        $this->assertTrue($option->orderItem->is($item));
    }

    public function test_order_item_option_has_no_relation_to_product_option(): void
    {
        $option = OrderItemOption::factory()->create();

        $this->assertFalse(method_exists($option, 'productOption'));
    }

    public function test_order_can_be_soft_deleted(): void
    {
        $order = Order::factory()->create();
        $order->delete();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_order_amounts_are_cast_to_integer(): void
    {
        $order = Order::factory()->create(['total_amount' => 35000]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'unit_price' => 20000, 'subtotal' => 20000]);
        $option = OrderItemOption::factory()->create(['order_item_id' => $item->id, 'extra_price' => 5000]);

        $this->assertIsInt($order->fresh()->total_amount);
        $this->assertIsInt($item->fresh()->unit_price);
        $this->assertIsInt($option->fresh()->extra_price);
    }
}
