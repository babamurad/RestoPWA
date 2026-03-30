<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чек заказа #{{ substr($order->id, 0, 8) }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-width: 300px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .order-info {
            margin-bottom: 20px;
        }
        
        .items {
            margin-bottom: 20px;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .item-name {
            flex: 1;
        }
        
        .item-qty {
            width: 30px;
            text-align: center;
        }
        
        .item-price {
            width: 80px;
            text-align: right;
        }
        
        .totals {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 14px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Печать</button>
    
    <div class="header">
        <h2>{{ $order->vendor->name ?? 'Ресторан' }}</h2>
        <p>Чек заказа</p>
    </div>
    
    <div class="order-info">
        <p><strong>Заказ #:</strong> {{ substr($order->id, 0, 8) }}</p>
        <p><strong>Дата:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
        <p><strong>Клиент:</strong> {{ $order->user->name ?? 'Гость' }}</p>
    </div>
    
    <div class="items">
        @foreach($order->items as $item)
        <div class="item">
            <span class="item-name">{{ $item['name'] }}</span>
            <span class="item-qty">x{{ $item['quantity'] }}</span>
            <span class="item-price">{{ number_format($item['price'] * $item['quantity'], 2) }} ₽</span>
        </div>
        @if(!empty($item['modifiers']))
            @foreach($item['modifiers'] as $modifier)
            <div class="item" style="font-size: 10px; color: #666;">
                <span class="item-name">  + {{ $modifier['name'] }}</span>
                <span class="item-price">{{ number_format($modifier['price'] ?? 0, 2) }} ₽</span>
            </div>
            @endforeach
        @endif
        @endforeach
    </div>
    
    <div class="totals">
        <div class="total-row">
            <span>Подытог:</span>
            <span>{{ number_format($order->total - $order->delivery_fee, 2) }} ₽</span>
        </div>
        <div class="total-row">
            <span>Доставка:</span>
            <span>{{ number_format($order->delivery_fee, 2) }} ₽</span>
        </div>
        <div class="total-row final">
            <span>ИТОГО:</span>
            <span>{{ number_format($order->total, 2) }} ₽</span>
        </div>
    </div>
    
    @if($order->address)
    <div class="delivery-address" style="margin-top: 20px;">
        <p><strong>Адрес доставки:</strong></p>
        <p>
            @if(is_array($order->address))
                {{ $order->address['street'] ?? '' }}, {{ $order->address['house'] ?? '' }}
                @if(!empty($order->address['apartment']))
                    , кв. {{ $order->address['apartment'] }}
                @endif
            @else
                {{ $order->address }}
            @endif
        </p>
    </div>
    @endif
    
    <div class="footer">
        <p>Спасибо за заказ!</p>
        <p>Оплата: {{ $order->payment_status === 'paid' ? 'Оплачено' : 'Ожидает оплаты' }}</p>
    </div>
</body>
</html>
