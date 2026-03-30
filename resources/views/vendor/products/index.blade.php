@extends('vendor.layout.app')

@section('title', 'Товары')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Товары</h2>
    <a href="{{ route('vendor.products.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
        Добавить товар
    </a>
</div>

<form method="GET" class="mb-4 flex gap-4">
    <select name="category" class="border rounded px-3 py-2">
        <option value="">Все категории</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    <select name="available" class="border rounded px-3 py-2">
        <option value="">Все</option>
        <option value="1" {{ request('available') === '1' ? 'selected' : '' }}>Доступные</option>
        <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>Недоступные</option>
    </select>
    <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">Фильтр</button>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Изображение</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Цена</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Категория</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($products as $product)
            <tr>
                <td class="px-6 py-4">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded">
                    @else
                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-400">Нет</div>
                    @endif
                </td>
                <td class="px-6 py-4">{{ $product->name }}</td>
                <td class="px-6 py-4">{{ number_format($product->price, 2) }} ₽</td>
                <td class="px-6 py-4">{{ $product->category->name ?? '-' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded {{ $product->is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $product->is_available ? 'Доступен' : 'Недоступен' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('vendor.products.edit', $product) }}" class="text-blue-600 hover:text-blue-900 mr-3">Редактировать</a>
                    <form action="{{ route('vendor.products.destroy', $product) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Удалить товар?')">Удалить</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $products->links() }}
</div>
@endsection
