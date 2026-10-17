<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            $request->validate([
                'user_id' => 'required_without:token|string',
                'token' => 'required_without:user_id|string',
            ]);

            $userId = $request->user_id;
        } else {
            $userId = $user->id;
        }

        $orders = Order::where('user_id', $userId)
            ->with(['statusHistory' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->latest()
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total' => $order->total,
                    'delivery_fee' => $order->delivery_fee,
                    'items' => $order->items,
                    'address' => $order->address,
                    'delivery_time' => $order->delivery_time,
                    'payment_method' => $order->payment_method,
                    'comment' => $order->comment,
                    'is_offline' => $order->is_offline ?? false,
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                    'status_history' => $order->statusHistory->map(function ($history) {
                        return [
                            'from_status' => $history->from_status,
                            'to_status' => $history->to_status,
                            'created_at' => $history->created_at->toIso8601String(),
                        ];
                    }),
                ];
            });

        return $this->success($orders);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::with(['statusHistory' => function ($query) {
            $query->latest();
        }])->findOrFail($id);

        if ($user && $order->user_id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        return $this->success([
            'id' => $order->id,
            'vendor_id' => $order->vendor_id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'delivery_fee' => $order->delivery_fee,
            'items' => $order->items,
            'address' => $order->address,
            'delivery_time' => $order->delivery_time,
            'payment_method' => $order->payment_method,
            'comment' => $order->comment,
            'is_offline' => $order->is_offline ?? false,
            'created_at' => $order->created_at->toIso8601String(),
            'updated_at' => $order->updated_at->toIso8601String(),
            'status_history' => $order->statusHistory->map(function ($history) {
                return [
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'created_at' => $history->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function assignCourier(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'courier_id' => 'required|string|uuid',
        ]);

        $user = Auth::user();
        if (! $user || ! ($user->isAdmin() || $user->isRestaurateur())) {
            return $this->error('Forbidden', 403);
        }

        $order = Order::with('restaurant')->findOrFail($id);

        // Restaurateur may only manage orders of their own restaurant.
        if (! $user->isAdmin() && (! $order->restaurant || ! $user->canAccessTenant($order->restaurant))) {
            return $this->error('Forbidden', 403);
        }

        if ($order->isPickup()) {
            return $this->error('Pickup order has no delivery', 422);
        }

        $courier = Courier::find($request->input('courier_id'));
        if (! $courier) {
            return $this->error('Courier not found', 404);
        }

        // A courier bound to a restaurant may only deliver that restaurant's orders.
        if ($courier->vendor_id && $courier->vendor_id !== $order->vendor_id) {
            return $this->error('Courier belongs to another restaurant', 422);
        }

        $order->update([
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
        ]);

        return $this->success($order->fresh());
    }
}
