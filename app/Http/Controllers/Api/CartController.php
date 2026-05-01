<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                    'name' => 'Unknown Product',
                    'reason' => 'Товар не найден или не принадлежит данному ресторану.',
                ];

                continue;
            }

            if (! $product->is_available) {
                $unavailableItems[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'reason' => 'Товар временно недоступен.',
                ];

                continue;
            }

            $serverItemUnitPrice = $product->price;
            $selectedModifiers = collect($item['modifiers'] ?? []);
            $modifiersData = [];

            if ($selectedModifiers->isNotEmpty() && $product->modifiers instanceof Collection) {
                foreach ($selectedModifiers as $modId) {
                    $modifier = $product->modifiers->firstWhere('id', $modId);
                    if ($modifier) {
                        $serverItemUnitPrice += $modifier['price'] ?? 0;
                        $modifiersData[] = $modifier;
                    }
                }
            }

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
                'name' => $product->name,
                'quantity' => $item['quantity'],
                'price' => (int) round($serverItemUnitPrice * 100),
                'line_total' => (int) round($lineTotal * 100),
                'image' => $product->image_url,
                'modifiers' => $modifiersData,
            ];
        }

        $deliveryFee = $restaurant->delivery_fee;
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
            'total' => (float) $total,
            'min_order' => (float) $restaurant->min_order,
            'is_min_order_met' => $subtotal >= $restaurant->min_order,
        ]);
    }
}
