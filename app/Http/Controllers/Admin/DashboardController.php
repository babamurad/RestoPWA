<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Menu\Models\Category;
use App\Domains\Menu\Models\Product;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'restaurants' => Restaurant::count(),
            'categories' => Category::count(),
            'products' => Product::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'orders_total' => Order::count(),
            'revenue_today' => Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total'),
        ];

        $recentOrders = Order::with('restaurant')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
