<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderSuccessController extends Controller
{
    public function show(string $id): View
    {
        $order = Order::with('statusHistory')->findOrFail($id);

        return view('order.success', [
            'order' => $order,
        ]);
    }
}
