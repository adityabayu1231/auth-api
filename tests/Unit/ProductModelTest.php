<?php

namespace Tests\Unit;

use App\Models\Cafe;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_cafe(): void
    {
        $cafe = Cafe::factory()->create();
        $product = Product::factory()->create(['cafe_id' => $cafe->id]);

        $this->assertTrue($product->cafe->is($cafe));
    }

    public function test_product_has_many_options(): void
    {
        $product = Product::factory()->create();
        ProductOption::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->fresh()->options);
    }

    public function test_option_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $option = ProductOption::factory()->create(['product_id' => $product->id]);

        $this->assertTrue($option->product->is($product));
    }

    public function test_product_is_available_cast_to_boolean(): void
    {
        $product = Product::factory()->create(['is_available' => 1]);

        $this->assertIsBool($product->fresh()->is_available);
    }

    public function test_product_can_be_soft_deleted(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->assertSoftDeleted($product);
    }
}