<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;

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

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        Gate::authorize('updateStatus', $order);

        $order = $this->orderService->updateStatus($order, $request->validated()['status']);

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Status order berhasil diperbarui.',
        ]);
    }
}
