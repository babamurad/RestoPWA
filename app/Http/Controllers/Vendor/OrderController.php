<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Domains\Order\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const STATUSES = [
        'pending' => 'Новый',
        'accepted' => 'Принят',
        'cooking' => 'Готовится',
        'ready' => 'Готов',
        'delivering' => 'Доставляется',
        'completed' => 'Завершен',
        'cancelled' => 'Отменен',
    ];
    
    public const PAYMENT_STATUSES = [
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'failed' => 'Ошибка оплаты',
        'refunded' => 'Возвращен',
    ];
    
    public function index(Request $request)
    {
        $vendorId = app('tenant')->id;
        
        $orders = Order::where('vendor_id', $vendorId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->until, fn($q) => $q->whereDate('created_at', '<=', $request->until))
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);
            
        return view('vendor.orders.index', compact('orders'));
    }
    
    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        
        $order->load(['user', 'statusHistory']);
        
        return view('vendor.orders.show', compact('order'));
    }
    
    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        
        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys(self::STATUSES)),
        ]);
        
        $order->update(['status' => $request->status]);
        
        return back()->with('success', 'Статус обновлен');
    }
    
    public function accept(Order $order)
    {
        $this->authorizeOrder($order);
        
        if ($order->status !== 'pending') {
            return back()->with('error', 'Можно принять только новые заказы');
        }
        
        $order->update(['status' => 'accepted']);
        
        return back()->with('success', 'Заказ принят');
    }
    
    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        
        if (!in_array($order->status, ['pending', 'accepted', 'cooking'])) {
            return back()->with('error', 'Нельзя отменить этот заказ');
        }
        
        $order->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Заказ отменен');
    }
    
    public function receipt(Order $order)
    {
        $this->authorizeOrder($order);
        
        return view('vendor.orders.receipt', compact('order'));
    }
    
    private function authorizeOrder(Order $order): void
    {
        if ($order->vendor_id !== app('tenant')->id) {
            abort(403);
        }
    }
}
