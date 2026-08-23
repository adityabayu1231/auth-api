<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderItemOptionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_options_has_no_foreign_key_to_product_options(): void
    {
        $foreignKeys = Schema::getForeignKeys('order_item_options');

        $referencesProductOptions = collect($foreignKeys)
            ->contains(fn($fk) => $fk['foreign_table'] === 'product_options');

        $this->assertFalse(
            $referencesProductOptions,
            'order_item_options seharusnya TIDAK punya foreign key ke product_options (aturan snapshot).'
        );
    }

    public function test_order_item_options_has_expected_columns_matching_product_options_types(): void
    {
        $this->assertTrue(Schema::hasColumns('order_item_options', [
            'option_type',
            'option_value',
            'extra_price',
        ]));

        $this->assertTrue(Schema::hasColumns('product_options', [
            'option_type',
            'option_value',
            'extra_price',
        ]));
    }
}
