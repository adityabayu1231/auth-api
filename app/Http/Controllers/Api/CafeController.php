<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCafeRequest;
use App\Http\Requests\UpdateCafeRequest;
use App\Models\Cafe;
use App\Services\CafeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CafeController extends Controller
{
    public function __construct(private CafeService $cafeService) {}

    public function index(Request $request)
    {
        $cafes = $this->cafeService->list($request->only(['city', 'is_active', 'sort', 'per_page']));

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cafes->items(),
                'pagination' => [
                    'current_page' => $cafes->currentPage(),
                    'per_page' => $cafes->perPage(),
                    'total' => $cafes->total(),
                    'last_page' => $cafes->lastPage(),
                ],
            ],
            'message' => 'Daftar cafe berhasil diambil.',
        ]);
    }

    public function show(Cafe $cafe)
    {
        return response()->json([
            'success' => true,
            'data' => $cafe,
            'message' => 'Detail cafe berhasil diambil.',
        ]);
    }

    public function store(CreateCafeRequest $request)
    {
        $cafe = $this->cafeService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $cafe,
            'message' => 'Cafe berhasil dibuat.',
        ], 201);
    }

    public function update(UpdateCafeRequest $request, Cafe $cafe)
    {
        Gate::authorize('update', $cafe);

        $cafe = $this->cafeService->update($cafe, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $cafe,
            'message' => 'Cafe berhasil diperbarui.',
        ]);
    }
}
