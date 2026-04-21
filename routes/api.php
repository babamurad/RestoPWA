<?php

use App\Domains\Menu\Http\Controllers\MenuController;
use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Http\Controllers\Api\OrderController as DomainOrderController;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\PushController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => response()->json(['status' => 'ok']))->name('api.ping');

Route::middleware([SetTenantContext::class, 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::bind('vendor', fn ($value) => Restaurant::where('slug', $value)->firstOrFail());
    Route::bind('product', fn ($value) => Product::findOrFail($value));

    Route::get('menu/{vendor}', [MenuController::class, 'index'])->name('api.menu.index');
    Route::get('menu/product/{product}', [MenuController::class, 'show'])->name('api.menu.product.show');

    Route::get('restaurants', fn () => Restaurant::active()
        ->select('id', 'slug', 'name', 'image', 'cover_image', 'rating', 'review_count', 'delivery_time', 'delivery_fee', 'min_order', 'is_active', 'description')
        ->get())->name('api.restaurants.index');

    Route::get('restaurants/{vendor}', fn (Restaurant $vendor) => response()->json($vendor))->name('api.restaurants.show');

    Route::get('categories', fn () => response()->json(['data' => Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'sort_order', 'parent_id'])]))->name('api.categories.index');

    Route::post('cart/sync', [CartController::class, 'sync'])->name('api.cart.sync');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('orders', [DomainOrderController::class, 'store'])->name('api.orders.store');
        Route::get('orders', [OrderController::class, 'index'])->name('api.v1.orders.index');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('api.v1.orders.show');
    });

    Route::post('push/subscribe', [PushController::class, 'subscribe'])->middleware('auth:sanctum')->name('api.push.subscribe');
    Route::post('push/unsubscribe', [PushController::class, 'unsubscribe'])->middleware('auth:sanctum')->name('api.push.unsubscribe');
});