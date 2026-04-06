<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['restaurant', 'user']);
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->search . '%')
                         ->orWhere('email', 'like', '%' . $request->search . '%');
                  })
                  ->orWhere('status', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('filter')) {
            $filter = $request->filter;
            if (isset(Order::FILTERS[$filter])) {
                $query->whereIn('status', Order::FILTERS[$filter]['statuses']);
            }
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('restaurant_id')) {
            $query->where('vendor_id', $request->restaurant_id);
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
        $order->load(['restaurant', 'user', 'statusHistory' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);
        
        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        return redirect()->route('admin.orders.show', $order);
    }

    public function update(Request $request, Order $order)
    {
        if ($request->has('status')) {
            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,preparing,delivering,delivered,cancelled',
            ]);

            $order->update(['status' => $validated['status']]);

            return redirect()->back()->with('success', 'Статус заказа обновлён на: ' . Order::STATUSES[$validated['status']]['label']);
        }

        return redirect()->back();
    }

    public function transition(Request $request, Order $order, string $newStatus)
    {
        if (!in_array($newStatus, $order->getNextStatuses())) {
            return redirect()->back()->with('error', 'Невозможно изменить статус на: ' . $newStatus);
        }

        $order->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Статус изменён на: ' . Order::STATUSES[$newStatus]['label']);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('admin.orders.index')->with('success', 'Заказ удалён');
    }
}
