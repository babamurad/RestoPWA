<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Order\Models\Order;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderSuccessController extends Controller
{
    use ApiResponses;
    
    public function show(string $id): View
    {
        $order = Order::with(['statusHistory', 'restaurant'])->findOrFail($id);

        if ($order->user_id && (! Auth::check() || Auth::id() !== $order->user_id)) {
            abort(403, 'У вас нет доступа к этому заказу.');
        }

        $signedTrackingUrl = URL::temporarySignedRoute(
            'order.track.guest',
            now()->addHours(24),
            ['orderId' => $id]
        );

        return view('order.success', [
            'order' => $order,
            'signedTrackingUrl' => $signedTrackingUrl,
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        $order = Order::with(['statusHistory', 'restaurant'])->findOrFail($id);

        if ($order->user_id && (! Auth::check() || Auth::id() !== $order->user_id)) {
            return $this->error('У вас нет доступа к этому заказу.', 403);
        }

        $signedTrackingUrl = URL::temporarySignedRoute(
            'api.order.track.guest',
            now()->addHours(24),
            ['orderId' => $id]
        );

        return $this->success([
            'order_id' => $order->id,
            'status' => $order->status,
            'total' => $order->total,
            'items' => $order->items,
            'address' => $order->address,
            'signed_tracking_url' => $signedTrackingUrl,
        ]);
    }
}
