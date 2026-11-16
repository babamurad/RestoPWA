<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers\Api;

use App\Domains\Geo\Services\GeoService;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\DTO\OrderSubmitDTO;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderRejection;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Validators\OrderPreconditionValidator;
use App\Enums\OrderRejectReason;
use App\Http\Traits\ApiResponses;
use App\Support\PIIMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController
{
    use ApiResponses;

    /** Stand-in coordinates for pickup orders, which carry no delivery point */
    private const PLACEHOLDER_LAT = 39.0886;

    private const PLACEHOLDER_LON = 63.5593;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderPreconditionValidator $preconditionValidator,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $traceId = (string) ($request->header('X-Trace-Id') ?? str()->uuid());

        // --- PREPROCESSING LAYER START ---
        // 1. Structure address
        $addressInput = $request->input('address');
        if (is_string($addressInput)) {
            $addressData = [
                'address' => $addressInput,
                'manual_address' => $addressInput,
                'entrance' => $request->input('entrance'),
                'floor' => $request->input('floor'),
                'apartment' => $request->input('apartment'),
                'courier_comment' => $request->input('comment'),
            ];
            $request->merge(['address' => $addressData]);
            $addressInput = $addressData;
        }

        $customerName = $request->input('customer_name') ?? $request->user()?->name ?? 'Покупатель';
        $customerPhone = $request->input('customer_phone') ?? $request->user()?->phone ?? '';
        $request->merge([
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
        ]);

        // 2. No coordinates from the client: geocode the address text. A failed lookup is not
        // replaced by a default point — the courier would be sent to the wrong place — so the
        // order is rejected below (INVALID_COORDINATES). Pickup needs no delivery point.
        $isPickupOrder = $request->input('delivery_type') === Order::DELIVERY_TYPE_PICKUP;
        $coordinatesMissing = false;
        if (is_array($addressInput)) {
            if (empty($addressInput['lat']) || empty($addressInput['lon'])) {
                $lat = null;
                $lon = null;

                $addressString = $addressInput['address'] ?? $addressInput['manual_address'] ?? '';
                if (! $isPickupOrder && ! empty($addressString)) {
                    try {
                        $geoService = app(GeoService::class);
                        $geoResult = $geoService->geocodeWithFallback($addressString);
                        if ($geoResult && ! empty($geoResult['lat']) && ! empty($geoResult['lon'])) {
                            $lat = $geoResult['lat'];
                            $lon = $geoResult['lon'];
                        }
                    } catch (\Throwable $e) {
                        Log::warning('[API Order] Geocoding fallback failed', [
                            'exception' => $e->getMessage(),
                        ]);
                    }
                }

                if ($lat === null || $lon === null) {
                    $coordinatesMissing = ! $isPickupOrder;
                    // Placeholder so the payload shape stays valid; never used for a delivery
                    $lat = self::PLACEHOLDER_LAT;
                    $lon = self::PLACEHOLDER_LON;
                }

                $addressInput['lat'] = $lat;
                $addressInput['lon'] = $lon;
            }

            $request->merge(['address' => $addressInput]);
        }

        // 3. Structure items — name, price and modifiers always come from the DB, never from
        // the client: the kitchen must see what is really being sold at the real price.
        $itemsInput = $request->input('items');
        $itemReject = null;
        if (is_array($itemsInput)) {
            $orderVendorId = (string) $request->input('vendor_id');
            $updatedItems = [];
            foreach ($itemsInput as $item) {
                if (is_array($item) && isset($item['product_id'])) {
                    $product = Str::isUuid((string) $item['product_id']) ? Product::find($item['product_id']) : null;

                    // Unknown product, or one from another restaurant's menu
                    if ($product && $orderVendorId !== '' && (string) $product->vendor_id !== $orderVendorId) {
                        $product = null;
                    }

                    if (! $product) {
                        $itemReject ??= OrderRejectReason::ITEM_NOT_FOUND;
                    } elseif (! $product->is_available) {
                        $itemReject ??= OrderRejectReason::ITEM_UNAVAILABLE;
                    }

                    $quantity = (int) ($item['quantity'] ?? 1);

                    // Server-side price: base product price from DB (MoneyCast → float currency units → cents)
                    $basePriceCents = $product ? (int) round($product->price * 100) : 0;

                    // Keep only modifiers that exist on the product, with their DB name and price
                    [$modifierPriceCents, $modifiers] = $product
                        ? self::resolveModifiersFromDb($product, (array) ($item['modifiers'] ?? []))
                        : [0, []];

                    $unitPrice = $basePriceCents + $modifierPriceCents;

                    $item['product_name'] = $product ? $product->name : 'Блюдо';
                    $item['unit_price'] = $unitPrice;
                    $item['total_price'] = $unitPrice * $quantity;
                    $item['modifiers'] = $modifiers;
                    $item['image'] = $product?->image;
                }
                $updatedItems[] = $item;
            }
            $request->merge(['items' => $updatedItems]);
        }

        // 4. Calculate total & delivery_fee — delivery fee is always recalculated server-side
        // via GeoService, the client-submitted value is never trusted (same policy as item prices above).
        if (is_array($request->input('items'))) {
            $itemsTotal = 0;
            foreach ($request->input('items') as $item) {
                $itemsTotal += (int) ($item['total_price'] ?? 0);
            }

            $deliveryFee = 0;
            $vendorId = $request->input('vendor_id');
            // Pickup orders are stored with delivery_type=pickup and are never
            // offered to couriers, so they carry no delivery fee.
            $isPickup = $request->input('delivery_type') === Order::DELIVERY_TYPE_PICKUP;
            if ($vendorId && ! $isPickup && is_array($request->input('address'))) {
                try {
                    $geoService = app(GeoService::class);
                    $lat = (float) $request->input('address.lat');
                    $lon = (float) $request->input('address.lon');
                    $deliveryFee = (int) round($geoService->calculateDeliveryFee($lat, $lon, $vendorId) * 100);
                } catch (\Throwable $e) {
                    Log::warning('[API Order] Delivery fee calculation failed', [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            $request->merge([
                'delivery_fee' => $deliveryFee,
                'total' => $itemsTotal + $deliveryFee,
            ]);
        }
        // --- PREPROCESSING LAYER END ---

        try {
            $validated = $request->validate([
                'vendor_id' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|string',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|integer|min:0',
                'items.*.total_price' => 'required|integer|min:0',
                'items.*.modifiers' => 'nullable|array',
                'items.*.image' => 'nullable|string',
                'address' => 'required|array',
                'address.lat' => 'required|numeric',
                'address.lon' => 'required|numeric',
                'address.address' => 'nullable|string',
                'customer_name' => 'required|string',
                'customer_phone' => 'required|string',
                'address.manual_address' => 'nullable|string',
                'address.landmark' => 'nullable|string',
                'address.entrance' => 'nullable|string',
                'address.floor' => 'nullable|string',
                'address.apartment' => 'nullable|string',
                'address.courier_comment' => 'nullable|string',
                'address.address_source' => 'nullable|string',
                'address.geolocate_attempted' => 'nullable|boolean',
                'address.geolocate_status' => 'nullable|string',
                'address.geolocate_accuracy_m' => 'nullable|numeric',
                'total' => 'required|integer|min:0',
                'delivery_fee' => 'nullable|integer|min:0',
                'delivery_type' => 'nullable|in:delivery,pickup',
                'delivery_time' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'comment' => 'nullable|string',
                'is_offline' => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            Log::warning('[API Order] Validation failed', [
                'trace_id' => $traceId,
                'reason' => OrderRejectReason::VALIDATION->value,
                'errors' => $e->errors(),
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::VALIDATION,
                details: $e->errors(),
                traceId: $traceId,
            );
        }

        $user = $request->user();

        if (! $user) {
            // No cookies, session contents or headers here: they hold session ids and tokens
            Log::warning('[API Order] Unauthenticated order attempt', [
                'trace_id' => $traceId,
                'has_session' => $request->hasSession(),
                'has_bearer' => $request->bearerToken() !== null,
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::UNAUTHORIZED,
                traceId: $traceId,
            );
        }

        // Precondition checks
        $preconditionReason = $this->preconditionValidator->validate($validated, $itemReject, $coordinatesMissing);
        if ($preconditionReason !== null) {
            Log::warning('[API Order] Precondition check failed', [
                'trace_id' => $traceId,
                'reason' => $preconditionReason->value,
                'user_id' => $user->id,
                'vendor_id' => $validated['vendor_id'],
            ]);

            return $this->errorResponse(
                reason: $preconditionReason,
                traceId: $traceId,
            );
        }

        Log::info('[API Order] store started', [
            'trace_id' => $traceId,
            'user_id' => $user->id,
            'vendor_id' => $validated['vendor_id'],
            'total' => $validated['total'],
            'item_count' => count($validated['items']),
        ]);

        // Idempotency check
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $existingOrder = Order::where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingOrder) {
                Log::info('[API Order] Duplicate order detected', [
                    'trace_id' => $traceId,
                    'order_id' => $existingOrder->id,
                ]);

                return $this->success([
                    'order_id' => $existingOrder->id,
                    'status' => $existingOrder->status,
                    'redirect_url' => route('order.success', $existingOrder->id),
                    'is_duplicate' => true,
                    'trace_id' => $traceId,
                ]);
            }
        }

        // Build DTO
        $validated['idempotency_key'] = $idempotencyKey;
        $dto = OrderSubmitDTO::fromArray($validated, $traceId);

        try {
            $order = $this->orderService->createOrder($dto->toOrderServiceData($user->id));

            Log::info('[API Order] Order created successfully', [
                'trace_id' => $traceId,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'vendor_id' => $order->vendor_id,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'redirect_url' => url('/order/success/'.$order->id),
                'trace_id' => $traceId,
            ], null, 201);

        } catch (\Exception $e) {
            Log::error('[API Order] Creation failed', [
                'trace_id' => $traceId,
                'reason' => OrderRejectReason::SERVER_ERROR->value,
                'exception' => $e->getMessage(),
                'payload_summary' => PIIMasker::maskOrderPayload([
                    'vendor_id' => $dto->vendorId,
                    'item_count' => count($dto->items),
                    'total' => $dto->total,
                ]),
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::SERVER_ERROR,
                traceId: $traceId,
            );
        }
    }

    /**
     * Match client-selected modifiers (by id or name) against the product's DB modifiers.
     * Returns the surcharge in cents and the matched modifiers with their DB name and price;
     * anything the product does not offer is dropped, client-sent names and prices are ignored.
     *
     * @return array{0: int, 1: list<array{id: mixed, name: mixed, price: float}>}
     */
    private static function resolveModifiersFromDb(Product $product, array $clientModifiers): array
    {
        $dbModifiers = $product->modifiers;
        if (! $dbModifiers instanceof Collection || $dbModifiers->isEmpty() || empty($clientModifiers)) {
            return [0, []];
        }

        // Flatten DB modifiers: groups with options → extract options; flat → keep as-is
        $flatDb = collect();
        foreach ($dbModifiers as $mod) {
            if (isset($mod['options']) && is_array($mod['options'])) {
                foreach ($mod['options'] as $option) {
                    $flatDb->push($option);
                }
            } else {
                $flatDb->push($mod);
            }
        }

        $surchargeCents = 0;
        $matched = [];
        foreach ($clientModifiers as $clientMod) {
            $clientModId = is_array($clientMod) ? ($clientMod['id'] ?? null) : (is_string($clientMod) ? $clientMod : null);
            $clientModName = is_array($clientMod) ? ($clientMod['name'] ?? null) : null;

            $dbMatch = null;

            if ($clientModId !== null) {
                $dbMatch = $flatDb->firstWhere('id', $clientModId);
            }
            if ($dbMatch === null && $clientModName !== null) {
                $dbMatch = $flatDb->firstWhere('name', $clientModName);
            }

            if (is_array($dbMatch)) {
                // Modifier prices in JSON are in currency units → convert to cents
                $price = (float) ($dbMatch['price'] ?? 0);
                $surchargeCents += (int) round($price * 100);
                $matched[] = [
                    'id' => $dbMatch['id'] ?? null,
                    'name' => $dbMatch['name'] ?? null,
                    'price' => $price,
                ];
            }
        }

        return [$surchargeCents, $matched];
    }

    private function errorResponse(
        OrderRejectReason $reason,
        array $details = [],
        string $traceId = '',
    ): JsonResponse {
        OrderRejection::record($reason, request()->input('vendor_id'), request()->user()?->id);

        return response()->json([
            'success' => false,
            'message' => $reason->userMessage(),
            'reason' => $reason->value,
            'details' => $details,
            'trace_id' => $traceId,
        ], $reason->httpStatus() > 0 ? $reason->httpStatus() : 422);
    }
}
