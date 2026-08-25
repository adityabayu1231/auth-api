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
use App\Http\Requests\UploadCafePhotoRequest;
use App\Http\Requests\UpdateOperatingHoursRequest;
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
        $cafe->load(['operatingHours', 'photos' => function ($query) {
            $query->orderBy('sort_order');
        }]);

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

    public function uploadPhoto(UploadCafePhotoRequest $request, Cafe $cafe)
    {
        Gate::authorize('update', $cafe);

        $photo = $this->cafeService->addPhoto(
            $cafe,
            $request->file('photo'),
            $request->input('sort_order', 0)
        );

        return response()->json([
            'success' => true,
            'data' => $photo,
            'message' => 'Foto cafe berhasil diunggah.',
        ], 201);
    }

    public function updateOperatingHours(UpdateOperatingHoursRequest $request, Cafe $cafe)
    {
        Gate::authorize('update', $cafe);

        $hours = $this->cafeService->updateOperatingHours($cafe, $request->input('hours'));

        return response()->json([
            'success' => true,
            'data' => $hours,
            'message' => 'Jam operasional cafe berhasil diperbarui.',
        ]);
    }
}
