<?php

use App\Domains\Order\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\OrderController;
use App\Http\Controllers\Vendor\SettingsController;
use App\Http\Controllers\Vendor\KanbanController;
use Illuminate\Support\Facades\Route;

use App\Domains\Menu\Models\Category;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // Fetch unique category names from all active restaurants
    $uniqueCategoryNames = Category::where('is_active', true)
        ->select('name')
        ->distinct()
        ->orderBy('name')
        ->get();

    $icons = [
        'Пицца' => '🍕',
        'Суши' => '🍣',
        'Роллы' => '🍱',
        'Бургеры' => '🍔',
        'Паста' => '🍝',
        'Салаты' => '🥗',
        'Закуски' => '🍟',
        'Супы' => '🍜',
        'Сеты' => '🍱',
        'Боулы' => '🥣',
        'Десерты' => '🍰',
        'Кофе' => '☕',
        'Выпечка' => '🥐',
    ];

    $categories = $uniqueCategoryNames->map(function($cat, $index) use ($icons) {
        return (object)[
            'id' => $index + 1,
            'name' => $cat->name,
            'icon' => $icons[$cat->name] ?? '🍴'
        ];
    });

    $popularRestaurants = Restaurant::where('is_active', true)->limit(10)->get();
    $restaurants = Restaurant::where('is_active', true)->paginate(20);

    return view('home', compact('categories', 'popularRestaurants', 'restaurants'));
})->name('home');

// Placeholder routes for navigation
Route::get('/restaurants', function () {
    return "Restaurants list";
})->name('restaurants.index');

Route::get('/restaurants/{restaurant:slug}', function (Restaurant $restaurant) {
    $restaurant->load(['categories' => function($q) {
        $q->where('is_active', true)->orderBy('sort_order')->with(['products' => function($pq) {
            $pq->where('is_available', true);
        }]);
    }]);

    return view('restaurants.show', compact('restaurant'));
})->name('restaurants.show');

Route::get('/cart', function () {
    return view('cart');
})->name('cart');

Route::get('/orders', function () {
    $user = Auth::user() ?? User::where('email', 'test@example.com')->first();
    $orders = $user ? \App\Domains\Order\Models\Order::where('user_id', $user->id)->latest()->get() : collect();
    return view('orders.index', compact('orders'));
})->name('orders.index');

Route::get('/profile', function () {
    $user = Auth::user() ?? User::where('email', 'test@example.com')->first();
    return view('profile.edit', compact('user'));
})->name('profile.edit');

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

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

Route::prefix('vendor')->name('vendor.')->middleware(['ensure.tenant'])->group(function () {
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('orders/kanban', [KanbanController::class, 'index'])->name('orders.kanban');
    Route::resource('orders', OrderController::class)->except(['create', 'store']);
    Route::post('orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});
