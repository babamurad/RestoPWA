@extends('layouts.admin.app')

@section('title', 'Заказы')
@section('header', 'Управление заказами')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Поиск</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Номер, имя, телефон..."
                   class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 w-64">
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
                @foreach(['pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                    {{ ucfirst($status) }}
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
        <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Фильтр</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Клиент</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сумма</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <span class="font-mono font-medium">#{{ $order->order_number }}</span>
                    @if($order->restaurant)
                    <div class="text-xs text-gray-500">{{ $order->restaurant->name }}</div>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium">{{ $order->customer_name }}</div>
                    <div class="text-sm text-gray-500">{{ $order->customer_phone }}</div>
                </td>
                <td class="px-6 py-4 font-medium">{{ number_format($order->total, 2) }} ₽</td>
                <td class="px-6 py-4">
                    @php $statusColors = ['pending' => 'yellow', 'confirmed' => 'blue', 'preparing' => 'orange', 'delivering' => 'purple', 'delivered' => 'green', 'cancelled' => 'red']; @endphp
                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $statusColors[$order->status] ?? 'gray' }}-100 text-{{ $statusColors[$order->status] ?? 'gray' }}-800">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $order->created_at->format('d.m.Y H:i') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900">Просмотр</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Заказы не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $orders->withQueryString()->links() }}
</div>
@endsection
