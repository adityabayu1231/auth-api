<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function store(CreateOrderRequest $request)
    {
        $validated = $request->validated();

        $order = $this->orderService->create(
            $request->user(),
            $validated['cafe_id'],
            $validated['items'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order berhasil dibuat.',
        ], 201);
    }
}
