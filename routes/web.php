<?php

use App\Domains\Order\Http\Controllers\OrderTrackingController;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderSuccessController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\Vendor\KanbanController;
use App\Domains\Order\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'))->name('home');
Route::get('/login', fn () => view('app'))->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', fn () => view('app'))->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/restaurants', fn () => view('app'))->name('restaurants.index');
Route::get('/restaurants/{slug}', fn () => view('app'))->name('restaurants.show');
Route::get('/cart', fn () => view('app'))->name('cart');
Route::get('/checkout', fn () => view('app'))->name('checkout');
Route::get('/orders', fn () => redirect('/profile'))->name('orders.index');
Route::get('/profile', fn () => view('app'))->name('profile.edit');



Route::view('/offline', 'offline');

Route::get('/order/{orderId}/track', [OrderTrackingController::class, 'track'])
    ->name('order.track')->middleware('auth');

Route::get('/order/{orderId}/track/guest', [OrderTrackingController::class, 'track'])
    ->name('order.track.guest')->middleware('signed');

Route::get('/order/success/{id}', [OrderSuccessController::class, 'show'])
    ->name('order.success');

Route::get('/api/order/success/{id}', [OrderSuccessController::class, 'apiShow'])
    ->name('api.order.success');

Route::get('/api/order/{orderId}/track', [OrderTrackingController::class, 'apiTrack'])
    ->name('api.order.track')->middleware('auth');

Route::get('/api/order/{orderId}/track/guest', [OrderTrackingController::class, 'apiTrack'])
    ->name('api.order.track.guest')->middleware('signed');

Route::match(['get', 'head'], '/api/ping', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'status' => 'ok',
        ],
    ]);
})->name('api.ping');

Route::middleware([SetTenantContext::class, 'auth'])->group(function () {
    Route::post('/api/v1/orders', [ApiOrderController::class, 'store'])->name('api.orders.store');
});


// Vendor orders kanban board
Route::get('/vendor/orders/kanban', [KanbanController::class, 'index'])
    ->name('vendor.orders.kanban')->middleware('auth');

// Vendor panel is now handled by Filament at /vendor

// Vue SPA entry point (Wildcard route for SPA routing)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!admin|vendor|api|filament|storage|build|offline).*$')->name('spa.entry');
