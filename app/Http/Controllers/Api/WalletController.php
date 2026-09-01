<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTopupRequestRequest;
use App\Services\TopupRequestService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        private TopupRequestService $topupRequestService,
    ) {}
    public function show(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 50));

        $result = $this->walletService->getBalanceAndHistory($request->user(), $perPage);

        $transactions = $result['transactions'];

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $result['balance'],
                'transactions' => [
                    'items' => $transactions->items(),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'last_page' => $transactions->lastPage(),
                    ],
                ],
            ],
            'message' => 'Data wallet berhasil diambil.',
        ]);
    }

    public function storeTopupRequest(CreateTopupRequestRequest $request)
    {
        $validated = $request->validated();

        $topupRequest = $this->topupRequestService->create(
            $request->user(),
            $validated['bank_account_id'],
            $validated['amount'],
            $validated['proof_image']
        );

        return response()->json([
            'success' => true,
            'data' => $topupRequest,
            'message' => 'Permintaan top-up berhasil diajukan, menunggu verifikasi admin.',
        ], 201);
    }
}
