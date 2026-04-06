<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const STATUSES = [
        'pending' => ['label' => 'Новый', 'color' => 'yellow'],
        'confirmed' => ['label' => 'Подтверждён', 'color' => 'blue'],
        'preparing' => ['label' => 'Готовится', 'color' => 'orange'],
        'delivering' => ['label' => 'Доставляется', 'color' => 'purple'],
        'delivered' => ['label' => 'Доставлен', 'color' => 'green'],
        'cancelled' => ['label' => 'Отменён', 'color' => 'red'],
    ];

    public function index(Request $request)
    {
        $query = Order::with('restaurant');
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        $restaurants = Restaurant::orderBy('name')->get();
        
        return view('admin.orders.index', compact('orders', 'restaurants'));
    }

    public function show(Order $order)
    {
        $order->load(['restaurant', 'statusHistory']);
        
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,delivering,delivered,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);
        
        $order->statusHistory()->create([
            'status' => $validated['status'],
            'changed_by' => auth()->id(),
            'notes' => $request->get('notes'),
        ]);

        return redirect()->back()->with('success', 'Статус заказа обновлён');
    }
}
