<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers;

use App\Domains\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderTrackingController
{
    public function track(string $orderId): View
    {
        $query = Order::with('statusHistory');

        if (! request()->hasValidSignature()) {
            $query->where('user_id', Auth::id());
        }

        $order = $query->findOrFail($orderId);

        $signedApiUrl = URL::temporarySignedRoute(
            'api.order.track.guest',
            now()->addHours(24),
            ['orderId' => $orderId]
        );

        return view('order.tracking', [
            'order' => $order,
            'orderId' => $orderId,
            'isGuest' => request()->hasValidSignature(),
            'signedApiUrl' => $signedApiUrl,
        ]);
    }

    public function apiTrack(string $orderId): JsonResponse
    {
        $query = Order::with('statusHistory');

        if (! request()->hasValidSignature()) {
            $query->where('user_id', Auth::id());
        }

        $order = $query->findOrFail($orderId);

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
