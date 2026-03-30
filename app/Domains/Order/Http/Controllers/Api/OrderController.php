<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers\Api;

use App\Domains\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController
{
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderService = app(OrderService::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|string',
            'user_id' => 'required|string',
            'address' => 'required|array',
            'items' => 'required|array',
            'total' => 'required|numeric',
            'delivery_fee' => 'nullable|numeric',
            'delivery_time' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $order = $this->orderService->createOrder([
            ...$validated,
            'created_via' => 'pwa',
            'is_offline' => $request->boolean('is_offline', false),
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'status' => $order->status,
        ], 201);
    }
}
