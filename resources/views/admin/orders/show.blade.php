@extends('layouts.admin.app')

@section('title', "Заказ #{$order->order_number}")
@section('header', "Заказ #{$order->order_number}")

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Информация о заказе</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Статус:</dt>
                    <dd>
                        @php $statusColors = ['pending' => 'yellow', 'confirmed' => 'blue', 'preparing' => 'orange', 'delivering' => 'purple', 'delivered' => 'green', 'cancelled' => 'red']; @endphp
                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $statusColors[$order->status] }}-100 text-{{ $statusColors[$order->status] }}-800">
                            {{ ucfirst($order->status) }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Дата:</dt>
                    <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                @if($order->restaurant)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Ресторан:</dt>
                    <dd>{{ $order->restaurant->name }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Клиент</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Имя:</dt>
                    <dd>{{ $order->customer_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Телефон:</dt>
                    <dd>{{ $order->customer_phone }}</dd>
                </div>
                @if($order->customer_address)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Адрес:</dt>
                    <dd>{{ $order->customer_address }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Изменить статус</h3>
            <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                @csrf @method('PUT')
                <div class="flex gap-3">
                    <select name="status" class="flex-1 px-4 py-2 border rounded-lg">
                        @foreach(['pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        Обновить
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Состав заказа</h3>
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="pb-2">Товар</th>
                        <th class="pb-2 text-center">Кол-во</th>
                        <th class="pb-2 text-right">Цена</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($order->items ?? [] as $item)
                    <tr>
                        <td class="py-2">{{ $item['name'] ?? 'N/A' }}</td>
                        <td class="py-2 text-center">{{ $item['quantity'] ?? 1 }}</td>
                        <td class="py-2 text-right">{{ number_format($item['price'] ?? 0, 2) }} ₽</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2">
                    <tr>
                        <td colspan="2" class="pt-3 font-semibold">Итого:</td>
                        <td class="pt-3 text-right font-bold">{{ number_format($order->total, 2) }} ₽</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($order->notes)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Комментарий</h3>
            <p class="text-gray-600">{{ $order->notes }}</p>
        </div>
        @endif

        <a href="{{ route('admin.orders.index') }}" class="inline-block px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
            &larr; К списку заказов
        </a>
    </div>
</div>
@endsection
