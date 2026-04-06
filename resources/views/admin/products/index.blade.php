@extends('layouts.admin.app')

@section('title', 'Товары')
@section('header', 'Управление товарами')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex-1 max-w-4xl">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск..." 
                   class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 w-64">
            <select name="restaurant_id" class="px-4 py-2 border rounded-lg">
                <option value="">Все рестораны</option>
                @foreach($restaurants as $r)
                <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="px-4 py-2 border rounded-lg">
                <option value="">Все категории</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">Все статусы</option>
                <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Доступен</option>
                <option value="unavailable" {{ request('status') === 'unavailable' ? 'selected' : '' }}>Недоступен</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Фильтр</button>
        </form>
    </div>
    <a href="{{ route('admin.products.create') }}" class="ml-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        + Добавить
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Категория</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $product->name }}</td>
                <td class="px-6 py-4 text-gray-500 font-mono text-sm">{{ $product->slug }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $product->category?->name ?? 'N/A' }}</td>
                <td class="px-6 py-4 font-medium">{{ number_format($product->price, 2) }} ₽</td>
                <td class="px-6 py-4">
                    @if($product->is_available)
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Доступен</span>
                    @else
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Недоступен</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-900 mr-3">Ред.</a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Удалить?')" class="text-red-600 hover:text-red-900">Удал.</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Товары не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $products->withQueryString()->links() }}
</div>
@endsection
