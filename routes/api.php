<?php

use App\Domains\Logistics\Http\Controllers\Api\CourierOrderController;
use App\Domains\Menu\Http\Controllers\MenuController;
use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Http\Controllers\Api\OrderController as DomainOrderController;
use App\Domains\Review\Http\Controllers\ReviewController;
use App\Domains\Vendor\Http\Controllers\RestaurantController;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Profile\AccountController;
use App\Http\Controllers\Api\Profile\AddressController;
use App\Http\Controllers\Api\Profile\FavoriteController;
use App\Http\Controllers\Api\Profile\NotificationSettingsController;
use App\Http\Controllers\Api\Profile\PaymentMethodController;
use App\Http\Controllers\Api\Profile\SupportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PushController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => response()->json(['status' => 'ok']))->name('api.ping');

// {id} is always a UUID: anything else is a 404 before it reaches the database
// (PostgreSQL raises an SQL error when a uuid column is compared with other text)
Route::middleware([SetTenantContext::class, 'throttle:api'])->prefix('v1')->whereUuid('id')->group(function () {
    Route::bind('vendor', fn ($value) => Restaurant::where('slug', $value)->firstOrFail());
    Route::bind('product', fn ($value) => Product::findOrFail($value));

    Route::get('menu/{vendor}', [MenuController::class, 'index'])->name('api.menu.index');
    Route::get('menu/product/{product}', [MenuController::class, 'show'])->name('api.menu.product.show');

    Route::get('restaurants', [RestaurantController::class, 'index'])->name('api.restaurants.index');
    Route::get('restaurants/{vendor}', [RestaurantController::class, 'show'])->name('api.restaurants.show');

    Route::get('restaurants/{vendor}/reviews', [ReviewController::class, 'index'])->name('api.restaurants.reviews');

    Route::get('categories', fn () => response()->json(['data' => Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'sort_order', 'parent_id'])]))->name('api.categories.index');

    Route::post('cart/sync', [CartController::class, 'sync'])->name('api.cart.sync');
    Route::post('cart/apply-promo', [CartController::class, 'applyPromo'])
        ->withoutMiddleware('throttle:api')->middleware('throttle:promo')->name('api.cart.apply-promo');

    // Geo endpoints (public — no auth required)
    Route::post('geo/zone-check', [GeoController::class, 'zoneCheck'])
        ->withoutMiddleware('throttle:api')->middleware('throttle:geo')->name('api.geo.zone-check');
    Route::post('geo/reverse', [GeoController::class, 'reverse'])
        ->withoutMiddleware('throttle:api')->middleware('throttle:geo')->name('api.geo.reverse');
    Route::post('telemetry', [GeoController::class, 'telemetry'])
        ->withoutMiddleware('throttle:api')->middleware('throttle:telemetry')->name('api.telemetry');

    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware('throttle:api')->middleware('throttle:login')->name('api.login');
    Route::post('register', [AuthController::class, 'register'])->withoutMiddleware('throttle:api')->middleware('throttle:register')->name('api.register');
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('user', [AuthController::class, 'user'])->name('api.user');

    Route::get('config', fn () => response()->json([
        'geolocate_enabled' => config('checkout.default_geolocate_enabled'),
    ]))->name('api.config');

    // Mobile app (Capacitor) — Bearer-token auth instead of the web session/CSRF flow
    Route::post('mobile/login', [AuthController::class, 'loginMobile'])->withoutMiddleware('throttle:api')->middleware('throttle:login')->name('api.mobile.login');
    Route::post('mobile/register', [AuthController::class, 'registerMobile'])->withoutMiddleware('throttle:api')->middleware('throttle:register')->name('api.mobile.register');
    Route::post('mobile/logout', [AuthController::class, 'logoutMobile'])->middleware('auth:sanctum')->name('api.mobile.logout');

    Route::post('orders', [DomainOrderController::class, 'store'])->withoutMiddleware('throttle:api')->middleware('throttle:orders')->name('api.orders.store');

    // Accepts either the web session (cookie) or a mobile Sanctum Bearer token
    Route::middleware('auth:web,sanctum')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('api.v1.orders.index');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('api.v1.orders.show');
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])
            ->middleware('throttle:10,1')
            ->name('api.v1.orders.cancel');
        Route::post('orders/{id}/review', [ReviewController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('api.v1.orders.review');
        Route::post('push/subscribe', [PushController::class, 'subscribe'])->name('api.push.subscribe');
        Route::post('push/unsubscribe', [PushController::class, 'unsubscribe'])->name('api.push.unsubscribe');

        // Profile features
        Route::prefix('profile')->group(function () {
            Route::put('locale', [AuthController::class, 'updateLocale'])->name('api.profile.locale');
            Route::delete('/', [AccountController::class, 'destroy'])
                ->middleware('throttle:5,1')
                ->name('api.profile.destroy');
            Route::apiResource('addresses', AddressController::class)->except(['show']);
            Route::get('notifications', [NotificationSettingsController::class, 'show'])->name('api.profile.notifications.show');
            Route::put('notifications', [NotificationSettingsController::class, 'update'])->name('api.profile.notifications.update');
            Route::get('favorites', [FavoriteController::class, 'index'])->name('api.profile.favorites.index');
            Route::put('favorites/{id}', [FavoriteController::class, 'store'])->name('api.profile.favorites.store');
            Route::delete('favorites/{id}', [FavoriteController::class, 'destroy'])->name('api.profile.favorites.destroy');
            Route::apiResource('payments', PaymentMethodController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('support', SupportController::class)->only(['index', 'store']);
        });

        // Courier API
        Route::prefix('courier')->group(function () {
            Route::get('orders', [CourierOrderController::class, 'index'])->name('api.courier.orders');
            Route::post('orders/{id}/accept', [CourierOrderController::class, 'accept'])->name('api.courier.accept');
            Route::post('orders/{id}/status', [CourierOrderController::class, 'updateStatus'])->name('api.courier.status');
            Route::post('location', [CourierOrderController::class, 'updateLocation'])->name('api.courier.location');
            Route::post('status', [CourierOrderController::class, 'updateProfileStatus'])->name('api.courier.profile_status');
            Route::get('offers', [CourierOrderController::class, 'offers'])->name('api.courier.offers');
            Route::post('offers/{id}/accept', [CourierOrderController::class, 'acceptOffer'])->name('api.courier.offers.accept');
            Route::post('offers/{id}/decline', [CourierOrderController::class, 'declineOffer'])->name('api.courier.offers.decline');
            Route::get('earnings', [CourierOrderController::class, 'earnings'])->name('api.courier.earnings');
        });

        Route::post('orders/{id}/assign', [OrderController::class, 'assignCourier'])->name('api.v1.orders.assign');
    });
});
