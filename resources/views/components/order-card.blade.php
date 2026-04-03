@props(['order'])

@php
    $statusConfig = [
        'pending' => ['label' => 'Ожидает', 'color' => 'bg-yellow-100 text-yellow-800'],
        'confirmed' => ['label' => 'Подтверждён', 'color' => 'bg-blue-100 text-blue-800'],
        'preparing' => ['label' => 'Готовится', 'color' => 'bg-orange-100 text-orange-800'],
        'delivering' => ['label' => 'Доставляется', 'color' => 'bg-purple-100 text-purple-800'],
        'delivered' => ['label' => 'Доставлен', 'color' => 'bg-green-100 text-green-800'],
    ];
    $status = $statusConfig[$order->status] ?? $statusConfig['pending'];
    $totalItems = $order->items->sum('quantity');
@endphp

<div class="p-4 bg-white rounded-2xl border border-gray-100 card-hover touch-feedback">
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-500">{{ $order->id }}</span>
                <span class="text-xs text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</span>
            </div>
            <h4 class="mt-1 font-semibold text-gray-900">{{ $order->restaurant->name }}</h4>
        </div>
        <div class="flex items-center gap-1 px-2 py-1 rounded-full {{ $status['color'] }}">
            <span class="text-xs font-medium">{{ $status['label'] }}</span>
        </div>
    </div>
    <div class="mt-3 text-sm text-gray-600">
        {{ $totalItems }} {{ $totalItems === 1 ? 'товар' : ($totalItems < 5 ? 'товара' : 'товаров') }}
    </div>
    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
        <span class="font-bold text-gray-900">{{ number_format($order->total, 0, '.', ' ') }} ₽</span>
        <div class="flex items-center gap-1 text-orange-500">
            <span class="text-sm font-medium">Подробнее</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </div>
    </div>
</div>