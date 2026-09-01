<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->input('per_page', 15), 50));

        $orders = $this->orderService->listByUser($request->user()->id, $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ],
            'message' => 'Riwayat order berhasil diambil.',
        ]);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('items.options');

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Detail order berhasil diambil.',
        ]);
    }

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

    public function cancel(Order $order)
    {
        Gate::authorize('cancel', $order);

        $order = $this->orderService->cancel($order);

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order berhasil dibatalkan.',
        ]);
    }
}
