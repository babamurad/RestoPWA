<?php

use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = Order::latest()->limit(5)->get();

echo "Last 5 orders:\n";
foreach ($orders as $order) {
    echo "ID: " . $order->id . " | User ID: " . ($order->user_id ?? 'NULL') . " | Status: " . $order->status . " | Created: " . $order->created_at . "\n";
}

if ($orders->isEmpty()) {
    echo "No orders found in DB.\n";
}
