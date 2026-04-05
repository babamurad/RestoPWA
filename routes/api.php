<?php

use App\Domains\Menu\Http\Controllers\MenuController;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Order\Http\Controllers\Api\OrderController as DomainOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\PushController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::middleware([SetTenantContext::class])->prefix('v1')->group(function () {
    Route::bind('vendor', function ($value) {
        return Restaurant::where('slug', $value)->firstOrFail();
    });

    Route::bind('product', function ($value) {
        return Product::findOrFail($value);
    });

    Route::get('menu/{vendor}', [MenuController::class, 'index'])
        ->name('api.menu.index');

    Route::get('menu/product/{product}', [MenuController::class, 'show'])
        ->name('api.menu.product.show');
    
    Route::get('restaurants', function () {
        return Restaurant::active()
            ->select('id', 'slug', 'name', 'image', 'cover_image', 'rating', 'review_count', 'delivery_time', 'delivery_fee', 'min_order', 'is_active', 'description')
            ->get();
    })->name('api.restaurants.index');

    Route::get('restaurants/{vendor}', function (Restaurant $vendor) {
        return response()->json($vendor);
    })->name('api.restaurants.show');

    Route::get('categories', function () {
        $categories = \App\Domains\Menu\Models\Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order', 'parent_id']);
        
        return response()->json([
            'data' => $categories,
        ]);
    })->name('api.categories.index');

    Route::post('orders', [DomainOrderController::class, 'store'])
        ->name('api.orders.store');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('v1/orders', [OrderController::class, 'index'])
        ->name('api.v1.orders.index');
    
    Route::get('v1/orders/{id}', [OrderController::class, 'show'])
        ->name('api.v1.orders.show');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('push/subscribe', [PushController::class, 'subscribe'])
        ->name('api.push.subscribe');
    
    Route::post('push/unsubscribe', [PushController::class, 'unsubscribe'])
        ->name('api.push.unsubscribe');
});
