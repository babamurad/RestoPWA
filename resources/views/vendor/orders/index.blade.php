@extends('vendor.layout.app')

@section('title', 'Заказы')

@section('content')
<h2 class="text-2xl font-bold mb-6">Заказы</h2>

<form method="GET" class="mb-4 flex flex-wrap gap-4">
    <select name="status" class="border rounded px-3 py-2">
        <option value="">Все статусы</option>
        @foreach(App\Http\Controllers\Vendor\OrderController::STATUSES as $key => $label)
            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select name="payment_status" class="border rounded px-3 py-2">
        <option value="">Все оплаты</option>
        @foreach(App\Http\Controllers\Vendor\OrderController::PAYMENT_STATUSES as $key => $label)
            <option value="{{ $key }}" {{ request('payment_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2">
    <input type="date" name="until" value="{{ request('until') }}" class="border rounded px-3 py-2">
    <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Фильтр</button>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Клиент</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сумма</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Оплата</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($orders as $order)
            <tr>
                <td class="px-6 py-4">{{ substr($order->id, 0, 8) }}</td>
                <td class="px-6 py-4">{{ $order->user->name ?? 'Гость' }}</td>
                <td class="px-6 py-4">{{ number_format($order->total, 2) }} ₽</td>
                <td class="px-6 py-4">
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
                </td>
                <td class="px-6 py-4">
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
                </td>
                <td class="px-6 py-4">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('vendor.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900 mr-3">Просмотр</a>
                    @if($order->status === 'pending')
                        <form action="{{ route('vendor.orders.accept', $order) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Принять</button>
                        </form>
                    @endif
                    @if(in_array($order->status, ['pending', 'accepted', 'cooking']))
                        <form action="{{ route('vendor.orders.cancel', $order) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Отменить заказ?')">Отклонить</button>
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $orders->links() }}
</div>
@endsection
