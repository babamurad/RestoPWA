<?php

use App\Domains\Order\Http\Controllers\OrderTrackingController;
use App\Domains\Order\Models\Order;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderSuccessController;
use App\Http\Controllers\RestaurantController;
use App\Domains\Order\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [RestaurantController::class, 'home'])->name('home');

Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');

Route::get('/restaurants/{restaurant:slug}', [RestaurantController::class, 'show'])->name('restaurants.show');

Route::get('/cart', function () {
    return view('cart');
})->name('cart');

use App\Livewire\Order\CheckoutWizard;

Route::get('/checkout', CheckoutWizard::class)->name('checkout');

Route::get('/orders', function () {
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    \Log::info('Web /orders accessed', [
        'user_id' => $user->id,
        'user_name' => $user->name
    ]);

    $orders = Order::where('user_id', $user->id)->latest()->get();

    return view('orders.index', compact('orders'));
})->name('orders.index')->middleware('auth');

Route::get('/profile', function () {
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return view('profile.edit', compact('user'));
})->name('profile.edit')->middleware('auth');

Route::get('/manifest.json', function () {
    return response()->json([
        'name' => 'RestoPWA',
        'short_name' => 'Resto',
        'start_url' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#fff',
        'theme_color' => '#FF6B35',
        'icons' => [
            [
                'src' => '/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => '/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ]);
});

Route::view('/offline', 'offline');

Route::get('/order/{orderId}/track', [OrderTrackingController::class, 'track'])
    ->name('order.track')->middleware('auth');

Route::get('/order/{orderId}/track/guest', [OrderTrackingController::class, 'track'])
    ->name('order.track.guest')->middleware('signed');

Route::get('/order/success/{id}', [OrderSuccessController::class, 'show'])
    ->name('order.success');

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


// Vendor panel is now handled by Filament at /vendor
