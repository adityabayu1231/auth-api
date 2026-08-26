<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function create(array $data): Product
    {
        $data['is_available'] = $data['is_available'] ?? true;

        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    public function listByCafe(int $cafeId, bool $onlyAvailable = true): LengthAwarePaginator
    {
        $query = Product::where('cafe_id', $cafeId);

        if ($onlyAvailable) {
            $query->where('is_available', true);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}
