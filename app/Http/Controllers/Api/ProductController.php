<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function store(CreateProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Produk berhasil dibuat.',
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Produk berhasil diperbarui.',
        ]);
    }
}
