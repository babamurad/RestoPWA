<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartController extends Controller
{
    /**
     * Sync cart items with server-side data (prices, availability).
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|uuid|exists:restaurants,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.modifiers' => 'nullable|array',
        ]);

        $vendorId = $validated['vendor_id'];
        $restaurant = Restaurant::findOrFail($vendorId);
        $inputItems = collect($validated['items']);
        
        $productIds = $inputItems->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->where('vendor_id', $vendorId)
            ->get()
            ->keyBy('id');

        $syncedItems = [];
        $errors = [];
        $subtotal = 0;

        foreach ($validated['items'] as $item) {
            $productId = $item['product_id'];
            /** @var Product|null $product */
            $product = $products->get($productId);

            if (!$product) {
                $errors[] = "Товар {$productId} не найден или не принадлежит данному ресторану.";
                continue;
            }

            if (!$product->is_available) {
                $errors[] = "Товар '{$product->name}' временно недоступен.";
                continue;
            }

            $itemPrice = $product->price;
            $selectedModifiers = collect($item['modifiers'] ?? []);
            $modifiersData = [];

            if ($selectedModifiers->isNotEmpty() && $product->modifiers instanceof Collection) {
                foreach ($selectedModifiers as $modId) {
                    $modifier = $product->modifiers->firstWhere('id', $modId);
                    if ($modifier) {
                        $itemPrice += $modifier['price'] ?? 0;
                        $modifiersData[] = $modifier;
                    }
                }
            }

            $lineTotal = $itemPrice * $item['quantity'];
            $subtotal += $lineTotal;

            $syncedItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $item['quantity'],
                'price' => $itemPrice,
                'line_total' => $lineTotal,
                'image' => $product->image_url,
                'modifiers' => $modifiersData,
            ];
        }

        $deliveryFee = $restaurant->delivery_fee;
        $total = $subtotal + $deliveryFee;

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $syncedItems,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'min_order' => $restaurant->min_order,
                'is_min_order_met' => $subtotal >= $restaurant->min_order,
            ],
            'errors' => $errors,
        ]);
    }
}
