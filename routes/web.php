<?php

use App\Domains\Order\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OrderSuccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'))->name('home');
Route::get('/login', fn () => view('app'))->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/register', fn () => view('app'))->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::get('/forgot-password', fn () => view('app'))->name('password.request');
Route::get('/reset-password', fn () => view('app'))->name('password.reset');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('redirect');
    Route::get('callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('callback');
});

Route::get('/restaurants', fn () => view('app'))->name('restaurants.index');
Route::get('/restaurants/{slug}', fn () => view('app'))->name('restaurants.show');
Route::get('/cart', fn () => view('app'))->name('cart');
Route::get('/checkout', fn () => view('app'))->name('checkout');
Route::get('/orders', fn () => view('app'))->name('orders.index');
Route::get('/profile', fn () => view('app'))->name('profile.edit');

Route::view('/offline', 'offline');

Route::get('/legal/{document}', [LegalController::class, 'show'])
    ->name('legal.show');

Route::get('/order/{orderId}/track', [OrderTrackingController::class, 'track'])
    ->name('order.track')->middleware('auth')->whereUuid('orderId');

Route::get('/order/{orderId}/track/guest', [OrderTrackingController::class, 'track'])
    ->name('order.track.guest')->middleware('signed')->whereUuid('orderId');

Route::get('/order/success/{id}', [OrderSuccessController::class, 'show'])
    ->name('order.success')->whereUuid('id');

Route::get('/api/order/success/{id}', [OrderSuccessController::class, 'apiShow'])
    ->name('api.order.success')->whereUuid('id');

Route::get('/api/order/{orderId}/track', [OrderTrackingController::class, 'apiTrack'])
    ->name('api.order.track')->middleware('auth')->whereUuid('orderId');

Route::get('/api/order/{orderId}/track/guest', [OrderTrackingController::class, 'apiTrack'])
    ->name('api.order.track.guest')->middleware('signed')->whereUuid('orderId');

Route::match(['get', 'head'], '/api/ping', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'status' => 'ok',
        ],
    ]);
})->name('api.ping');

// Vendor panel is now handled by Filament at /vendor

// Vue SPA entry point (Wildcard route for SPA routing)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!admin|vendor|api|filament|storage|build|offline).*$')->name('spa.entry');
