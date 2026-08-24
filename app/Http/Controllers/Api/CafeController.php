<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCafeRequest;
use App\Http\Requests\UpdateCafeRequest;
use App\Models\Cafe;
use App\Services\CafeService;
use App\Services\CafeStatusService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class CafeController extends Controller
{
    public function __construct(
        private CafeService $cafeService,
        private CafeStatusService $cafeStatusService,
    ) {}

    public function index(Request $request)
    {
        $cafes = $this->cafeService->list($request->only(['city', 'is_active', 'sort', 'per_page']));

        /** @var LengthAwarePaginator $cafes */
        $cafes->getCollection()->load('operatingHours');

        $items = $cafes->getCollection()->map(function (Cafe $cafe) {
            $data = $cafe->toArray();
            $data['open_status'] = $this->cafeStatusService->getOpenStatus($cafe)->value;
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
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
        $cafe->load('operatingHours');

        $data = $cafe->toArray();
        $data['open_status'] = $this->cafeStatusService->getOpenStatus($cafe)->value;

        return response()->json([
            'success' => true,
            'data' => $data,
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
