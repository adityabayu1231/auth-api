<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProductOptionsRequest;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductOptionRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Cafe;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use App\Services\ProductOptionService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ProductOptionService $productOptionService,
    ) {}

    public function indexByCafe(Request $request, Cafe $cafe)
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $products = $this->productService->listByCafe($cafe->id, true, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ],
            'message' => 'Katalog produk berhasil diambil.',
        ]);
    }

    public function show(Product $product)
    {
        $product->load('options');

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Detail produk berhasil diambil.',
        ]);
    }

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

    public function addOptions(AddProductOptionsRequest $request, Product $product)
    {
        Gate::authorize('update', $product);

        $options = $this->productOptionService->addOptions($product, $request->validated()['options']);

        return response()->json([
            'success' => true,
            'data' => $options,
            'message' => 'Opsi produk berhasil ditambahkan.',
        ], 201);
    }

    public function updateOption(UpdateProductOptionRequest $request, ProductOption $productOption)
    {
        $product = $productOption->product;

        Gate::authorize('update', $product);

        $option = $this->productOptionService->updateOption($productOption, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $option,
            'message' => 'Opsi produk berhasil diperbarui.',
        ]);
    }
}
