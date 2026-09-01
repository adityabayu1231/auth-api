<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

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
}
