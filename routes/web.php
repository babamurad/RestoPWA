<?php

use App\Domains\Order\Http\Controllers\OrderTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
    ->name('order.track');

Route::get('/api/order/{orderId}/track', [OrderTrackingController::class, 'apiTrack'])
    ->name('api.order.track');
