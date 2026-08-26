<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductOption;
use App\Services\ProductOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_options_creates_all_options_for_product(): void
    {
        $product = Product::factory()->create();
        $service = new ProductOptionService();

        $result = $service->addOptions($product, [
            ['option_type' => 'size', 'option_value' => 'Regular', 'extra_price' => 0, 'is_default' => false],
            ['option_type' => 'size', 'option_value' => 'Large', 'extra_price' => 5000, 'is_default' => true],
            ['option_type' => 'sweetness', 'option_value' => '50%', 'extra_price' => 0, 'is_default' => true],
        ]);

        $this->assertCount(3, $result);
        $this->assertCount(3, $product->fresh()->options);
    }

    public function test_update_option_unsets_previous_default_of_same_type(): void
    {
        $product = Product::factory()->create();
        $oldDefault = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'sweetness',
            'option_value' => '50%',
            'is_default' => true,
        ]);
        $newOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'sweetness',
            'option_value' => '100%',
            'is_default' => false,
        ]);

        $service = new ProductOptionService();
        $service->updateOption($newOption, ['is_default' => true]);

        $this->assertTrue($newOption->fresh()->is_default);
        $this->assertFalse($oldDefault->fresh()->is_default);
    }

    public function test_update_option_does_not_affect_other_option_types(): void
    {
        $product = Product::factory()->create();
        $sizeDefault = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'size',
            'is_default' => true,
        ]);
        $sweetnessOption = ProductOption::factory()->create([
            'product_id' => $product->id,
            'option_type' => 'sweetness',
            'is_default' => false,
        ]);

        $service = new ProductOptionService();
        $service->updateOption($sweetnessOption, ['is_default' => true]);

        $this->assertTrue($sizeDefault->fresh()->is_default);
    }
}
