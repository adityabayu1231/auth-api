<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopupRequest;
use App\Services\TopupRequestService;
use Illuminate\Http\Request;

class TopupRequestController extends Controller
{
    public function __construct(private TopupRequestService $topupRequestService) {}

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 50));
        $status = $request->input('status');

        $topupRequests = $this->topupRequestService->listByStatus($status, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $topupRequests->items(),
                'pagination' => [
                    'current_page' => $topupRequests->currentPage(),
                    'per_page' => $topupRequests->perPage(),
                    'total' => $topupRequests->total(),
                    'last_page' => $topupRequests->lastPage(),
                ],
            ],
            'message' => 'Daftar topup request berhasil diambil.',
        ]);
    }

    public function approve(Request $request, TopupRequest $topupRequest)
    {
        $topupRequest = $this->topupRequestService->approve($topupRequest, $request->user());

        return response()->json([
            'success' => true,
            'data' => $topupRequest,
            'message' => 'Topup request berhasil disetujui.',
        ]);
    }

    public function reject(Request $request, TopupRequest $topupRequest)
    {
        $topupRequest = $this->topupRequestService->reject($topupRequest, $request->user());

        return response()->json([
            'success' => true,
            'data' => $topupRequest,
            'message' => 'Topup request berhasil ditolak.',
        ]);
    }
}
