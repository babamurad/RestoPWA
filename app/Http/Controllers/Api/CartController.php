<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Menu\Services\ProductModifiers;
use App\Domains\Promo\Services\PromoCodeService;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use ApiResponses;

    /**
     * Sync cart items with server-side data (prices, availability).
     */
    public function sync(Request $request): JsonResponse
    {
        $traceId = (string) ($request->header('X-Trace-Id') ?? str()->uuid());

        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:restaurants,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric',
            'items.*.modifiers' => 'nullable|array',
            // Echoed back, so the app can tell apart lines of one dish with different choices
            'items.*.key' => 'nullable|string|max:255',
        ]);

        Log::info('[Cart Sync] Started', [
            'trace_id' => $traceId,
            'vendor_id' => $validated['vendor_id'],
            'item_count' => count($validated['items']),
        ]);

        $vendorId = $validated['vendor_id'];
        $restaurant = Restaurant::findOrFail($vendorId);
        $inputItems = collect($validated['items']);

        $productIds = $inputItems->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->where('vendor_id', $vendorId)
            ->get()
            ->keyBy('id');

        $validatedItems = [];
        $priceChanges = [];
        $unavailableItems = [];
        $subtotal = 0;

        foreach ($validated['items'] as $item) {
            $productId = $item['product_id'];
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (! $product) {
                $unavailableItems[] = [
                    'product_id' => $productId,
                    'key' => $item['key'] ?? null,
                    'name' => 'Unknown Product',
                    'reason' => 'Товар не найден или не принадлежит данному ресторану.',
                ];

                continue;
            }

            if (! $product->is_available) {
                $unavailableItems[] = [
                    'product_id' => $product->id,
                    'key' => $item['key'] ?? null,
                    'name' => $product->name,
                    'reason' => 'Товар временно недоступен.',
                ];

                continue;
            }

            // Price and check the choice against the dish's modifier groups
            $choice = ProductModifiers::resolve(ProductModifiers::groups($product->modifiers), $item['modifiers'] ?? []);

            if ($choice['violations'] !== []) {
                $unavailableItems[] = [
                    'product_id' => $product->id,
                    'key' => $item['key'] ?? null,
                    'name' => $product->name,
                    'reason' => 'Состав добавок изменился: добавьте блюдо в корзину заново.',
                ];

                continue;
            }

            $serverItemUnitPrice = $product->price + $choice['price'];
            $modifiersData = $choice['selected'];

            // Check for price changes
            $clientItemUnitPrice = isset($item['price']) ? (float) $item['price'] / 100 : null;
            if ($clientItemUnitPrice !== null && abs($clientItemUnitPrice - $serverItemUnitPrice) > 0.01) {
                $priceChanges[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'old_price' => $clientItemUnitPrice,
                    'new_price' => $serverItemUnitPrice,
                ];
            }

            $lineTotal = $serverItemUnitPrice * $item['quantity'];
            $subtotal += $lineTotal;

            $validatedItems[] = [
                'product_id' => $product->id,
                'key' => $item['key'] ?? null,
                'name' => $product->name,
                'quantity' => $item['quantity'],
                'price' => (int) round($serverItemUnitPrice * 100),
                'line_total' => (int) round($lineTotal * 100),
                'image' => $product->image_url,
                'modifiers' => $modifiersData,
            ];
        }

        // District pricing: the exact fee depends on the address, so the cart shows
        // the cheapest district price as an estimate until checkout picks a district.
        $deliveryFeeFrom = $restaurant->delivery_fee_from;
        $deliveryFee = $deliveryFeeFrom ?? $restaurant->delivery_fee;
        $total = $subtotal + $deliveryFee;

        Log::info('[Cart Sync] Completed', [
            'trace_id' => $traceId,
            'valid_items' => count($validatedItems),
            'price_changes' => count($priceChanges),
            'unavailable' => count($unavailableItems),
            'subtotal' => $subtotal,
        ]);

        return $this->success([
            'validated_items' => $validatedItems,
            'price_changes' => $priceChanges,
            'unavailable_items' => $unavailableItems,
            'items' => $validatedItems,
            'subtotal' => $subtotal,
            'delivery_fee' => (float) $deliveryFee,
            'delivery_fee_is_estimate' => $deliveryFeeFrom !== null,
            'total' => (float) $total,
            'min_order' => (float) $restaurant->min_order,
            'is_min_order_met' => $subtotal >= $restaurant->min_order,
        ]);
    }

    /**
     * Check a promo code against the cart and tell what it would take off.
     * Nothing is redeemed here: the code is taken when the order is placed. All amounts are
     * minor units (tyiyn), like the order API; the food total is priced from the database.
     */
    public function applyPromo(Request $request, PromoCodeService $promoCodes): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40',
            'vendor_id' => 'required|uuid|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.modifiers' => 'nullable|array',
        ]);

        $products = Product::whereIn('id', collect($validated['items'])->pluck('product_id')->unique())
            ->where('vendor_id', $validated['vendor_id'])
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                return $this->promoResponse(false, 'items_changed', 'Состав корзины изменился. Обновите корзину.');
            }

            $choice = ProductModifiers::resolve(ProductModifiers::groups($product->modifiers), $item['modifiers'] ?? []);

            if ($choice['violations'] !== []) {
                return $this->promoResponse(false, 'items_changed', 'Состав корзины изменился. Обновите корзину.');
            }

            $subtotal += ((int) round($product->price * 100) + (int) round($choice['price'] * 100)) * (int) $item['quantity'];
        }

        $result = $promoCodes->evaluate($validated['code'], $request->user('sanctum')?->id, $validated['vendor_id'], $subtotal);

        if ($result['reason'] !== null) {
            return $this->promoResponse(false, $result['reason']->value, $result['reason']->message());
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'code' => $result['promo']->code,
                'discount' => $result['discount'],
                'subtotal' => $subtotal,
                'description' => $result['promo']->description,
            ],
        ]);
    }

    private function promoResponse(bool $valid, string $reason, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'reason' => $reason,
            'data' => ['valid' => $valid],
        ], 422);
    }
}
