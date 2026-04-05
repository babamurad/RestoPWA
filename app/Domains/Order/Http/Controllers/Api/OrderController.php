<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers\Api;

use App\Domains\Order\Services\OrderService;
use App\Models\User;
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
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.total_price' => 'required|numeric',
            'address' => 'nullable|array',
            'address.street' => 'required_with:address|string',
            'address.house' => 'nullable|string',
            'address.apartment' => 'nullable|string',
            'address.comment' => 'nullable|string',
            'total' => 'required|numeric',
            'delivery_fee' => 'nullable|numeric',
            'delivery_time' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'comment' => 'nullable|string',
            'is_offline' => 'nullable|boolean',
        ]);

        $userId = $this->resolveUserId($request);
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => 'User authentication required',
            ], 401);
        }

        $items = array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'name' => $item['product_name'] ?? 'Product',
                'image' => $item['image'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => (int) ($item['unit_price'] * 100),
                'total_price' => (int) ($item['total_price'] * 100),
                'modifiers' => $item['modifiers'] ?? [],
            ];
        }, $validated['items']);

        $order = $this->orderService->createOrder([
            'vendor_id' => $validated['vendor_id'],
            'user_id' => $userId,
            'address' => $validated['address'] ?? [],
            'items' => $items,
            'total' => (int) ($validated['total'] * 100),
            'delivery_fee' => (int) (($validated['delivery_fee'] ?? 0) * 100),
            'delivery_time' => $validated['delivery_time'] ?? null,
            'payment_method' => $validated['payment_method'] ?? 'card',
            'comment' => $validated['comment'] ?? null,
            'created_via' => 'pwa',
            'is_offline' => $request->boolean('is_offline', false),
        ]);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'status' => $order->status,
            'redirect_url' => route('order.success', $order->id),
        ], 201);
    }

    private function resolveUserId(Request $request): ?string
    {
        if ($request->user()) {
            return $request->user()->id;
        }

        if ($request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
            return $user?->id;
        }

        return null;
    }
}
