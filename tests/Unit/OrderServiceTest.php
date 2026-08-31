<?php

namespace Tests\Unit;

use App\Exceptions\ProductUnavailableException;
use App\Models\Cafe;
use App\Models\Product;
use App\Models\ProductOption;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

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

        $service = new OrderService();

        $result = $service->calculateTotal([
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'option_ids' => [$sizeOption->id, $sweetnessOption->id],
            ],
        ], $cafe->id);

        // unit_price = 20000 + 5000 + 0 = 25000, subtotal = 25000 x 2 = 50000
        $this->assertEquals(50000, $result['total_amount']);
        $this->assertEquals(25000, $result['items'][0]['unit_price']);
        $this->assertEquals(50000, $result['items'][0]['subtotal']);
    }

    public function test_sums_total_across_multiple_items(): void
    {
        $cafe = Cafe::factory()->create();
        $productA = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 20000, 'is_available' => true]);
        $productB = Product::factory()->create(['cafe_id' => $cafe->id, 'base_price' => 15000, 'is_available' => true]);

        $service = new OrderService();

        $result = $service->calculateTotal([
            ['product_id' => $productA->id, 'quantity' => 2, 'option_ids' => []],
            ['product_id' => $productB->id, 'quantity' => 1, 'option_ids' => []],
        ], $cafe->id);

        // (20000 x 2) + (15000 x 1) = 55000
        $this->assertEquals(55000, $result['total_amount']);
    }

    public function test_rejects_unavailable_product(): void
    {
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id, 'is_available' => false]);

        $service = new OrderService();

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

        $service = new OrderService();

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

        $service = new OrderService();

        $this->expectException(ProductUnavailableException::class);
        $service->calculateTotal([
            ['product_id' => $product->id, 'quantity' => 1, 'option_ids' => [$foreignOption->id]],
        ], $cafe->id);
    }
}
