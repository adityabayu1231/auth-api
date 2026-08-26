<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Support\Collection;

class ProductOptionService
{
    public function addOptions(Product $product, array $options): Collection
    {
        return collect($options)->map(function (array $option) use ($product) {
            return $product->options()->create($option);
        });
    }

    public function updateOption(ProductOption $option, array $data): ProductOption
    {
        if (! empty($data['is_default'])) {
            ProductOption::where('product_id', $option->product_id)
                ->where('option_type', $data['option_type'] ?? $option->option_type)
                ->where('id', '!=', $option->id)
                ->update(['is_default' => false]);
        }

        $option->update($data);

        return $option->fresh();
    }
}
