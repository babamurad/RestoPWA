<?php

use App\Domains\Menu\Http\Controllers\MenuController;
use App\Domains\Menu\Models\Product;
use App\Domains\Vendor\Models\Restaurant;
use App\Domains\Order\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
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
        return \App\Domains\Vendor\Models\Restaurant::active()
            ->select('id', 'slug', 'name', 'image', 'rating', 'delivery_time', 'delivery_fee')
            ->get();
    })->name('api.restaurants.index');
});

Route::post('orders', [OrderController::class, 'store'])
    ->name('api.orders.store');
