@extends('vendor.layout.app')

@section('title', 'Заказ #' . substr($order->id, 0, 8))

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Заказ #{{ substr($order->id, 0, 8) }}</h2>
    <div class="flex gap-2">
        <a href="{{ route('vendor.orders.receipt', $order) }}" target="_blank" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded inline-flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Печать чека
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Информация о заказе</h3>
        
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Клиент:</span>
                <span class="font-medium">{{ $order->user->name ?? 'Гость' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Сумма:</span>
                <span class="font-medium">{{ number_format($order->total, 2) }} ₽</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Доставка:</span>
                <span class="font-medium">{{ number_format($order->delivery_fee, 2) }} ₽</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Статус:</span>
                <span class="px-2 py-1 text-xs rounded 
                    @switch($order->status)
                        @case('pending') bg-gray-100 text-gray-800 @break
                        @case('accepted') bg-yellow-100 text-yellow-800 @break
                        @case('cooking') bg-blue-100 text-blue-800 @break
                        @case('ready') bg-indigo-100 text-indigo-800 @break
                        @case('completed') bg-green-100 text-green-800 @break
                        @case('cancelled') bg-red-100 text-red-800 @break
                    @endswitch
                ">
                    {{ App\Http\Controllers\Vendor\OrderController::STATUSES[$order->status] ?? $order->status }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Оплата:</span>
                <span class="px-2 py-1 text-xs rounded 
                    @switch($order->payment_status)
                        @case('pending') bg-yellow-100 text-yellow-800 @break
                        @case('paid') bg-green-100 text-green-800 @break
                        @case('failed') bg-red-100 text-red-800 @break
                        @case('refunded') bg-blue-100 text-blue-800 @break
                    @endswitch
                ">
                    {{ App\Http\Controllers\Vendor\OrderController::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Дата:</span>
                <span class="font-medium">{{ $order->created_at->format('d.m.Y H:i') }}</span>
            </div>
        </div>
        
        <div class="mt-6 border-t pt-4">
            <h4 class="font-medium mb-2">Изменить статус:</h4>
            <form action="{{ route('vendor.orders.update-status', $order) }}" method="POST" class="flex gap-2">
                @csrf
                <select name="status" class="border rounded px-3 py-2 flex-1">
                    @foreach(App\Http\Controllers\Vendor\OrderController::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Сохранить</button>
            </form>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Адрес доставки</h3>
        
        @if($order->address)
            @if(is_array($order->address))
                <p>{{ $order->address['street'] ?? '' }}, {{ $order->address['house'] ?? '' }}
                @if(!empty($order->address['apartment']))
                    , кв. {{ $order->address['apartment'] }}
                @endif
                </p>
                @if(!empty($order->address['comment']))
                    <p class="text-gray-600 mt-2">Комментарий: {{ $order->address['comment'] }}</p>
                @endif
            @else
                <p>{{ $order->address }}</p>
            @endif
        @else
            <p class="text-gray-500">Адрес не указан</p>
        @endif
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow mt-6">
    <h3 class="text-lg font-semibold mb-4">Товары</h3>
    
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Название</th>
                <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Кол-во</th>
                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Цена</th>
                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Сумма</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($order->items as $item)
            <tr>
                <td class="px-4 py-3">
                    {{ $item['name'] }}
                    @if(!empty($item['modifiers']))
                        <div class="text-sm text-gray-500">
                            @foreach($item['modifiers'] as $modifier)
                                <div>+ {{ $modifier['name'] }} ({{ number_format($modifier['price'] ?? 0, 2) }} ₽)</div>
                            @endforeach
                        </div>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">{{ $item['quantity'] }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($item['price'], 2) }} ₽</td>
                <td class="px-4 py-3 text-right">{{ number_format($item['price'] * $item['quantity'], 2) }} ₽</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($order->statusHistory->count() > 0)
<div class="bg-white p-6 rounded-lg shadow mt-6">
    <h3 class="text-lg font-semibold mb-4">История статусов</h3>
    
    <div class="space-y-3">
        @foreach($order->statusHistory as $history)
        <div class="flex items-center gap-4 text-sm">
            <span class="text-gray-500">{{ $history->created_at->format('d.m.Y H:i') }}</span>
            <span class="text-gray-600">
                {{ $history->from_status ? (App\Http\Controllers\Vendor\OrderController::STATUSES[$history->from_status] ?? $history->from_status) : 'Новый' }}
                →
                {{ App\Http\Controllers\Vendor\OrderController::STATUSES[$history->to_status] ?? $history->to_status }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="mt-6">
    <a href="{{ route('vendor.orders.index') }}" class="text-blue-600 hover:text-blue-800">← К списку заказов</a>
</div>
@endsection
