<?php

declare(strict_types=1);

namespace App\Domains\Order\Http\Controllers\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Order\DTO\OrderSubmitDTO;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Validators\OrderPreconditionValidator;
use App\Enums\OrderRejectReason;
use App\Http\Traits\ApiResponses;
use App\Support\PIIMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController
{
    use ApiResponses;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderPreconditionValidator $preconditionValidator,
    ) {
    }

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

        // 2. Fallback coordinates & geocoding if missing lat/lon
        if (is_array($addressInput)) {
            if (empty($addressInput['lat']) || empty($addressInput['lon'])) {
                $lat = 39.0886; // Default latitude for Turkmenabat
                $lon = 63.5593; // Default longitude for Turkmenabat
                
                $addressString = $addressInput['address'] ?? $addressInput['manual_address'] ?? '';
                if (!empty($addressString)) {
                    try {
                        $geoService = app(\App\Domains\Geo\Services\GeoService::class);
                        $geoResult = $geoService->geocodeWithFallback($addressString);
                        if ($geoResult && !empty($geoResult['lat']) && !empty($geoResult['lon'])) {
                            $lat = $geoResult['lat'];
                            $lon = $geoResult['lon'];
                        }
                    } catch (\Throwable $e) {
                        Log::warning('[API Order] Geocoding fallback failed', [
                            'exception' => $e->getMessage()
                        ]);
                    }
                }
                
                $addressInput['lat'] = $lat;
                $addressInput['lon'] = $lon;
            }

            $request->merge(['address' => $addressInput]);
        }

        // 3. Structure items — always recalculate prices from DB, never trust client
        $itemsInput = $request->input('items');
        if (is_array($itemsInput)) {
            $updatedItems = [];
            foreach ($itemsInput as $item) {
                if (is_array($item) && isset($item['product_id'])) {
                    $productId = $item['product_id'];
                    $product = Product::find($productId);

                    $productName = $item['product_name'] ?? $item['name'] ?? ($product ? $product->name : 'Блюдо');
                    $quantity = (int) ($item['quantity'] ?? 1);
                    $clientModifiers = $item['modifiers'] ?? [];

                    // Server-side price: base product price from DB (MoneyCast → float currency units → cents)
                    $basePriceCents = $product ? (int) round($product->price * 100) : 0;

                    // Recalculate modifier surcharge from DB, ignoring client-sent prices
                    $modifierPriceCents = 0;
                    if (! empty($clientModifiers) && $product) {
                        $modifierPriceCents = self::calculateModifierPriceFromDb($product, $clientModifiers);
                    }

                    $unitPrice = $basePriceCents + $modifierPriceCents;
                    $totalPrice = $unitPrice * $quantity;

                    $item['product_name'] = $productName;
                    $item['unit_price'] = $unitPrice;
                    $item['total_price'] = $totalPrice;
                    $item['modifiers'] = $clientModifiers;
                    $item['image'] = $item['image'] ?? ($product?->image);
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
            if ($vendorId && is_array($request->input('address'))) {
                try {
                    $geoService = app(\App\Domains\Geo\Services\GeoService::class);
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
                'delivery_time' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'comment' => 'nullable|string',
                'is_offline' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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
            \Illuminate\Support\Facades\Log::error('Order Auth Failed', [
                'has_session' => $request->hasSession(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : 'none',
                'cookies' => $request->cookies->all(),
                'session_all' => $request->hasSession() ? $request->session()->all() : [],
                'headers' => $request->headers->all(),
            ]);

            return $this->errorResponse(
                reason: OrderRejectReason::UNAUTHORIZED,
                traceId: $traceId,
            );
        }

        // Precondition checks
        $preconditionReason = $this->preconditionValidator->validate($validated);
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
     * Calculate the modifier price surcharge in cents from the DB product modifiers,
     * matching client-selected modifiers by id or name. Never trusts client-sent prices.
     */
    private static function calculateModifierPriceFromDb(Product $product, array $clientModifiers): int
    {
        $dbModifiers = $product->modifiers;
        if (! $dbModifiers instanceof Collection || $dbModifiers->isEmpty() || empty($clientModifiers)) {
            return 0;
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

            if ($dbMatch !== null) {
                // Modifier prices in JSON are in currency units → convert to cents
                $surchargeCents += (int) round(((float) ($dbMatch['price'] ?? 0)) * 100);
            }
        }

        return $surchargeCents;
    }

    private function errorResponse(
        OrderRejectReason $reason,
        array $details = [],
        string $traceId = '',
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $reason->userMessage(),
            'reason' => $reason->value,
            'details' => $details,
            'trace_id' => $traceId,
        ], $reason->httpStatus() > 0 ? $reason->httpStatus() : 422);
    }
}
