<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Order\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
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

        return response()->json([
            'success' => true,
            'data' => $orders,
            'total' => $orders->count(),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::with(['statusHistory' => function ($query) {
            $query->latest();
        }])->findOrFail($id);

        if ($user && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
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
            ],
        ]);
    }
}
