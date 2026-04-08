<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers;

use App\Domains\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderTrackingController
{
    public function track(string $orderId): View
    {
        $order = Order::with('statusHistory')
            ->where('user_id', Auth::id())
            ->findOrFail($orderId);

        return view('order.tracking', [
            'order' => $order,
            'orderId' => $orderId,
        ]);
    }

    public function apiTrack(string $orderId): JsonResponse
    {
        $order = Order::with('statusHistory')
            ->where('user_id', Auth::id())
            ->findOrFail($orderId);

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'address' => $order->address,
            'items' => $order->items,
            'total' => $order->total,
            'status_history' => $order->statusHistory->map(fn ($h) => [
                'status' => $h->to_status,
                'timestamp' => $h->created_at,
            ]),
            'is_delivering' => $order->status === 'delivering',
        ]);
    }
}
