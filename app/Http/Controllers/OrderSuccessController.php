<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderSuccessController extends Controller
{
    public function show(string $id): View
    {
        $order = Order::with(['statusHistory', 'restaurant'])->findOrFail($id);

        // Security check: if order has a user_id, ensure it matches current user
        if ($order->user_id && (! Auth::check() || Auth::id() !== $order->user_id)) {
            abort(403, 'У вас нет доступа к этому заказу.');
        }

        return view('order.success', [
            'order' => $order,
        ]);
    }
}
