@extends('layouts.admin.app')

@section('title', "Заказ #" . substr($order->id, 0, 8))
@section('header', "Заказ #" . substr($order->id, 0, 8))

@section('content')
@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Информация о заказе</h3>
                <span class="px-3 py-1 text-sm rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 font-medium">
                    {{ $order->status_label }}
                </span>
            </div>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm text-gray-500">ID заказа</dt>
                    <dd class="font-mono text-sm">{{ $order->id }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Дата создания</dt>
                    <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Сумма заказа</dt>
                    <dd class="text-lg font-bold">{{ number_format($order->total, 2) }} ₽</dd>
                </div>
                @if($order->delivery_fee > 0)
                <div>
                    <dt class="text-sm text-gray-500">Доставка</dt>
                    <dd>{{ number_format($order->delivery_fee, 2) }} ₽</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm text-gray-500">Оплата</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $order->payment_status === 'paid' ? 'Оплачен' : 'Ожидает' }}
                        </span>
                    </dd>
                </div>
                @if($order->restaurant)
                <div>
                    <dt class="text-sm text-gray-500">Ресторан</dt>
                    <dd>{{ $order->restaurant->name }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Состав заказа</h3>
            <div class="space-y-3">
                @forelse($order->items ?? [] as $item)
                <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                    <div class="flex-1">
                        <div class="font-medium">{{ $item['name'] ?? 'N/A' }}</div>
                        @if(isset($item['modifiers']) && count($item['modifiers']) > 0)
                        <div class="text-sm text-gray-500">
                            @foreach($item['modifiers'] as $mod)
                            {{ $mod['name'] }}@if(!$loop->last), @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="text-right ml-4">
                        <div class="font-medium">{{ $item['quantity'] ?? 1 }} x {{ number_format($item['price'] ?? 0, 2) }} ₽</div>
                        <div class="text-sm text-gray-500">{{ number_format(($item['quantity'] ?? 1) * ($item['price'] ?? 0), 2) }} ₽</div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Состав заказа не найден</p>
                @endforelse
            </div>
            <div class="mt-4 pt-4 border-t-2 border-gray-200">
                <div class="flex justify-between text-lg font-bold">
                    <span>Итого:</span>
                    <span>{{ number_format($order->total, 2) }} ₽</span>
                </div>
            </div>
        </div>

        @if($order->comment)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Комментарий</h3>
            <p class="text-gray-600">{{ $order->comment }}</p>
        </div>
        @endif

        @if($order->statusHistory->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">История статусов</h3>
            <div class="space-y-4">
                @foreach($order->statusHistory as $history)
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 mt-2 rounded-full bg-{{ Order::STATUSES[$history->to_status]['color'] ?? 'gray' }}-500"></div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ Order::STATUSES[$history->to_status]['label'] ?? $history->to_status }}</span>
                            <span class="text-sm text-gray-500">{{ $history->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($history->from_status)
                        <div class="text-sm text-gray-500">
                            Был: {{ Order::STATUSES[$history->from_status]['label'] ?? $history->from_status }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Клиент</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500">Имя</dt>
                    <dd class="font-medium">{{ $order->customer_name ?? $order->user?->name ?? 'Гость' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Телефон</dt>
                    <dd>
                        @if($order->customer_phone)
                        <a href="tel:{{ $order->customer_phone }}" class="text-blue-600 hover:underline">
                            {{ $order->customer_phone }}
                        </a>
                        @else
                        {{ $order->user?->phone ?? '-' }}
                        @endif
                    </dd>
                </div>
                @if($order->customer_address)
                <div>
                    <dt class="text-sm text-gray-500">Адрес доставки</dt>
                    <dd>{{ $order->customer_address }}</dd>
                </div>
                @endif
                @if($order->user)
                <div>
                    <dt class="text-sm text-gray-500">Email</dt>
                    <dd>{{ $order->user->email }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Быстрые действия</h3>
            
            @if($order->getNextStatuses())
            <div class="space-y-2">
                <p class="text-sm text-gray-500 mb-3">Изменить статус:</p>
                @foreach($order->getNextStatuses() as $nextStatus)
                <form action="{{ route('admin.orders.transition', [$order, $nextStatus]) }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="w-full px-4 py-2 text-sm rounded-lg transition-colors
                                   bg-{{ Order::STATUSES[$nextStatus]['color'] }}-100 text-{{ Order::STATUSES[$nextStatus]['color'] }}-800 
                                   hover:bg-{{ Order::STATUSES[$nextStatus]['color'] }}-200 font-medium"
                            onclick="return confirm('Change to: {{ Order::STATUSES[$nextStatus]['label'] }}')">
                        {{ Order::STATUSES[$nextStatus]['label'] }}
                    </button>
                </form>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500">Заказ завершён или отменён</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Ручное изменение статуса</h3>
            <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                @csrf @method('PUT')
                <select name="status" class="w-full px-4 py-2 border rounded-lg mb-3">
                    @foreach(Order::STATUSES as $key => $status)
                    <option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>
                        {{ $status['label'] }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                    Обновить статус
                </button>
            </form>
        </div>

        <a href="{{ route('admin.orders.index') }}" 
           class="block text-center px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
            &larr; К списку заказов
        </a>
    </div>
</div>
@endsection
