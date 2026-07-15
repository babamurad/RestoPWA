<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers\Api;

use App\Domains\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CourierOrderController extends Controller
{
    /**
     * Get available orders (ready for pickup) or currently assigned orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courierId = $user->courierProfile->id ?? null;
        if (!$courierId) {
            return response()->json(['message' => 'Courier profile not found'], 404);
        }

        $vendorId = $user->courierProfile->vendor_id;

        $availableQuery = Order::where('status', Order::STATUS_READY_FOR_PICKUP)
            ->whereNull('courier_id');
        
        if ($vendorId) {
            $availableQuery->where('vendor_id', $vendorId);
        }

        $availableOrders = $availableQuery->get();

        $activeOrders = Order::where('courier_id', $courierId)
            ->whereIn('status', [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERING])
            ->get();

        return response()->json([
            'data' => [
                'available' => $availableOrders,
                'active' => $activeOrders,
            ],
        ]);
    }

    /**
     * Accept an order.
     */
    public function accept(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if (!$courier) {
            return response()->json(['message' => 'Courier profile not found'], 404);
        }

        $order = Order::findOrFail($id);

        if ($order->courier_id !== null && $order->courier_id !== $courier->id) {
            return response()->json(['message' => 'Order already assigned to another courier'], 409);
        }

        if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
            return response()->json(['message' => 'Order is not ready for pickup'], 400);
        }

        if ($courier->vendor_id && $order->vendor_id !== $courier->vendor_id) {
            return response()->json(['message' => 'Forbidden vendor'], 403);
        }

        $order->update([
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERING,
        ]);

        return response()->json([
            'message' => 'Order accepted',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Update order status (e.g., to DELIVERED).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys(Order::STATUSES)),
        ]);

        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->courier_id !== $user->courierProfile->id) {
            return response()->json(['message' => 'Order not assigned to you'], 403);
        }

        $newStatus = $request->input('status');

        if (!in_array($newStatus, $order->getNextStatuses(), true)) {
            return response()->json(['message' => 'Invalid status transition'], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status updated',
            'data' => $order->fresh(),
        ]);
    }

    /**
     * Update courier live location.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if ($courier) {
            $courier->update([
                'current_lat' => $request->input('lat'),
                'current_lon' => $request->input('lon'),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update courier availability status.
     */
    public function updateProfileStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:offline,available,busy',
        ]);

        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if ($courier) {
            $courier->update(['status' => $request->input('status')]);
        }

        return response()->json(['success' => true, 'status' => $request->input('status')]);
    }

    /**
     * Get courier earnings history.
     */
    public function earnings(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if (!$courier) {
            return response()->json(['data' => []]);
        }

        $earnings = $courier->earnings()->with('order')->latest()->get();

        return response()->json(['data' => $earnings]);
    }
}
