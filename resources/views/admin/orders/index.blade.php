@extends('layouts.admin.app')

@section('title', 'Заказы')
@section('header', 'Управление заказами')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="space-y-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ID заказа, клиент..."
                       class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 w-64">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Быстрый фильтр</label>
                <select name="filter" class="px-4 py-2 border rounded-lg">
                    <option value="">Все заказы</option>
                    @foreach(\App\Domains\Order\Models\Order::FILTERS as $key => $filter)
                    <option value="{{ $key }}" {{ request('filter') === $key ? 'selected' : '' }}>
                        {{ $filter['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Ресторан</label>
                <select name="restaurant_id" class="px-4 py-2 border rounded-lg">
                    <option value="">Все</option>
                    @foreach($restaurants as $r)
                    <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Статус</label>
                <select name="status" class="px-4 py-2 border rounded-lg">
                    <option value="">Все</option>
                    @foreach(\App\Domains\Order\Models\Order::STATUSES as $key => $status)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $status['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Дата от</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Дата до</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="px-4 py-2 border rounded-lg">
            </div>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                Фильтр
            </button>
            @if(request()->hasAny(['search', 'filter', 'status', 'restaurant_id', 'date_from', 'date_to']))
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50">
                Сбросить
            </a>
            @endif
        </div>
    </form>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
    {{ session('error') }}
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Клиент</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ресторан</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сумма</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Время</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50 {{ $order->status === 'pending' ? 'bg-yellow-50' : '' }}">
                <td class="px-6 py-4">
                    <span class="font-mono font-medium text-sm">{{ substr($order->id, 0, 8) }}...</span>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium">{{ $order->customer_name ?? $order->user?->name ?? 'Гость' }}</div>
                    <div class="text-sm text-gray-500">{{ $order->customer_phone ?? $order->user?->phone ?? '-' }}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm">{{ $order->restaurant?->name ?? 'N/A' }}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-bold">{{ number_format($order->total, 2) }} ₽</span>
                    @if($order->delivery_fee > 0)
                    <div class="text-xs text-gray-500">+ доставка {{ number_format($order->delivery_fee, 2) }} ₽</div>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $order->status_color }}-100 text-{{ $order->status_color }}-800 font-medium">
                        {{ $order->status_label }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-500">{{ $order->created_at->format('d.m.Y') }}</div>
                    <div class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.orders.show', $order) }}" 
                           class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                            Открыть
                        </a>
                        @if($order->getNextStatuses())
                        <div class="relative group">
                            <button class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200">
                                Действие
                            </button>
                            <div class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border hidden group-hover:block z-10">
                                @foreach($order->getNextStatuses() as $nextStatus)
                                <form action="{{ route('admin.orders.transition', [$order, $nextStatus]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 rounded-lg"
                                            onclick="return confirm('Change status to: {{ \App\Domains\Order\Models\Order::STATUSES[$nextStatus]['label'] }}?')">
                                        {{ \App\Domains\Order\Models\Order::STATUSES[$nextStatus]['label'] }}
                                    </button>
                                </form>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center gap-2">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Заказы не найдены</span>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $orders->withQueryString()->links() }}
</div>
@endsection
