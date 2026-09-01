<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopupRequest;
use App\Services\TopupRequestService;
use Illuminate\Http\Request;

class TopupRequestController extends Controller
{
    public function __construct(private TopupRequestService $topupRequestService) {}

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
