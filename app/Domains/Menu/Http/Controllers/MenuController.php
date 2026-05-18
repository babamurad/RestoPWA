<?php

declare(strict_types=1);

namespace App\Domains\Menu\Http\Controllers;

use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController
{
    public function index(Request $request, Restaurant $vendor): JsonResponse
    {
        $request->validate([
            'category_id' => 'nullable|integer',
        ]);

        $vendorId = $vendor->vendor_id ?? $vendor->id;
        $categoryId = $request->category_id;
        $cacheTags = ['menu', "vendor:{$vendorId}"];
        $cacheKey = "menu.{$vendorId}.category.".($categoryId ?? 'all');
        $cacheTime = 3600;

        // Force clear cache to apply new price formatting
        try {
            Cache::tags($cacheTags)->flush();
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
        }
        
        $fetchData = function () use ($vendorId, $categoryId) {
            $categories = $this->getCategories($vendorId);
            $products = $this->getProducts($vendorId, $categoryId);
            $filters = $this->getPriceFilters($vendorId, $categoryId);

            return compact('categories', 'products', 'filters');
        };

        // Safe fallback for cache drivers (like file, database) that do not support tags
        $supportsTags = true;
        try {
            Cache::tags(['test']);
        } catch (\BadMethodCallException $e) {
            $supportsTags = false;
        }

        if ($supportsTags) {
            $data = Cache::tags($cacheTags)->remember($cacheKey, $cacheTime, $fetchData);
        } else {
            $data = Cache::remember($cacheKey, $cacheTime, $fetchData);
        }

        return response()->json($data);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'description' => $product->description,
            'modifiers' => $product->modifiers ?? collect(),
            'image_url' => $product->image ? (str_starts_with($product->image, 'http') ? $product->image : asset('storage/'.$product->image)) : null,
            'is_available' => $product->is_available,
            'weight_g' => $product->weight_g,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
        ]);
    }

    private function getCategories(string $vendorId): array
    {
        $allCategories = Category::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->buildCategoryTree($allCategories);
    }

    private function buildCategoryTree($categories): array
    {
        $categoryMap = [];
        $roots = [];

        foreach ($categories as $category) {
            $categoryMap[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'children' => [],
            ];
        }

        foreach ($categories as $category) {
            if ($category->parent_id && isset($categoryMap[$category->parent_id])) {
                $categoryMap[$category->parent_id]['children'][] = &$categoryMap[$category->id];
            } else {
                $roots[] = &$categoryMap[$category->id];
            }
        }

        return $roots;
    }

    private function getProducts(string $vendorId, ?int $categoryId = null)
    {
        $query = Product::where('vendor_id', $vendorId)
            ->available()
            ->with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->paginate(50);

        return [
            'data' => ProductResource::collection($products)->resolve(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ];
    }

    private function getPriceFilters(string $vendorId, ?int $categoryId = null): array
    {
        $query = Product::where('vendor_id', $vendorId)->available();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $prices = $query->pluck('price')->toArray();

        return [
            'min' => ! empty($prices) ? (int) min($prices) : 0,
            'max' => ! empty($prices) ? (int) max($prices) : 0,
        ];
    }
}
