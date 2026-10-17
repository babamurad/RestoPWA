<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers\Api;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierOffer;
use App\Domains\Logistics\Services\CourierDispatcher;
use App\Domains\Order\Models\Order;
use App\Events\CourierLocationUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierOrderController extends Controller
{
    /**
     * Get available orders (ready for pickup) or currently assigned orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courierId = $user->courierProfile->id ?? null;
        if (! $courierId) {
            return response()->json(['message' => 'Courier profile not found'], 404);
        }

        // Bound couriers see their restaurants' orders, the general pool sees all
        $restaurantIds = $user->courierProfile->restaurantIds();

        $availableQuery = Order::where('status', Order::STATUS_READY_FOR_PICKUP)
            ->where('delivery_type', Order::DELIVERY_TYPE_DELIVERY)
            ->whereNull('courier_id');

        if ($restaurantIds !== []) {
            $availableQuery->whereIn('vendor_id', $restaurantIds);
        }

        $availableOrders = $availableQuery->get();

        $activeOrders = Order::where('courier_id', $courierId)
            ->where('delivery_type', Order::DELIVERY_TYPE_DELIVERY)
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
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if (! $courier) {
            return response()->json(['message' => 'Courier profile not found'], 404);
        }

        // Lock the order row so two couriers accepting at the same time
        // cannot both pass the checks below: the second one waits for the
        // first transaction to commit and then sees courier_id already set.
        return DB::transaction(function () use ($id, $courier): JsonResponse {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($order->courier_id !== null && $order->courier_id !== $courier->id) {
                return response()->json(['message' => 'Order already assigned to another courier'], 409);
            }

            if ($order->isPickup()) {
                return response()->json(['message' => 'Pickup order has no delivery'], 422);
            }

            if ($order->status !== Order::STATUS_READY_FOR_PICKUP) {
                return response()->json(['message' => 'Order is not ready for pickup'], 400);
            }

            if (! $courier->canServe($order->vendor_id)) {
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
        });
    }

    /**
     * Update order status (e.g., to DELIVERED).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(Order::STATUSES)),
        ]);

        $user = Auth::user();
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order = Order::findOrFail($id);

        if ($order->courier_id !== $user->courierProfile->id) {
            return response()->json(['message' => 'Order not assigned to you'], 403);
        }

        if ($order->isPickup()) {
            return response()->json(['message' => 'Pickup order has no delivery'], 422);
        }

        $newStatus = $request->input('status');

        if (! in_array($newStatus, $order->getNextStatuses(), true)) {
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
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if ($courier) {
            $courier->update([
                'current_lat' => $request->input('lat'),
                'current_lon' => $request->input('lon'),
            ]);

            try {
                broadcast(new CourierLocationUpdated($courier));
            } catch (\Throwable $e) {
                Log::warning('[Courier] Location broadcast failed', ['exception' => $e->getMessage()]);
            }
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
        if (! $user || ! $user->isCourier()) {
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
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $courier = $user->courierProfile;
        if (! $courier) {
            return response()->json(['data' => []]);
        }

        $earnings = $courier->earnings()->with('order')->latest()->get();

        return response()->json(['data' => $earnings]);
    }

    /**
     * GET /api/v1/courier/offers — orders automatically offered to me, still open.
     */
    public function offers(): JsonResponse
    {
        $courier = $this->currentCourier();
        if ($courier instanceof JsonResponse) {
            return $courier;
        }

        $offers = CourierOffer::with('order')
            ->where('courier_id', $courier->id)
            ->where('status', CourierOffer::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(fn (CourierOffer $offer) => [
                'offer_id' => $offer->id,
                'expires_at' => $offer->expires_at->toIso8601String(),
                'order' => $offer->order,
            ]);

        return response()->json(['data' => $offers]);
    }

    /**
     * POST /api/v1/courier/offers/{id}/accept
     */
    public function acceptOffer(string $id, CourierDispatcher $dispatcher): JsonResponse
    {
        $courier = $this->currentCourier();
        if ($courier instanceof JsonResponse) {
            return $courier;
        }

        $offer = CourierOffer::findOrFail($id);

        try {
            $order = $dispatcher->accept($offer, $courier);
        } catch (\RuntimeException $e) {
            $code = in_array($e->getCode(), [403, 409], true) ? $e->getCode() : 409;

            return response()->json(['message' => $e->getMessage()], $code);
        }

        return response()->json(['message' => 'Order accepted', 'data' => $order->fresh()]);
    }

    /**
     * POST /api/v1/courier/offers/{id}/decline — the order goes to the next courier.
     */
    public function declineOffer(string $id, CourierDispatcher $dispatcher): JsonResponse
    {
        $courier = $this->currentCourier();
        if ($courier instanceof JsonResponse) {
            return $courier;
        }

        $offer = CourierOffer::findOrFail($id);
        if ($offer->courier_id !== $courier->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $dispatcher->decline($offer);

        return response()->json(['message' => 'Offer declined']);
    }

    private function currentCourier(): Courier|JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->isCourier()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $user->courierProfile ?? response()->json(['message' => 'Courier profile not found'], 404);
    }
}
