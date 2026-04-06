@extends('layouts.admin.app')

@section('title', 'Категории')
@section('header', 'Управление категориями')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex-1 max-w-2xl">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск..." 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
            <select name="restaurant_id" class="px-4 py-2 border rounded-lg">
                <option value="">Все рестораны</option>
                @foreach($restaurants as $r)
                <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Фильтр</button>
        </form>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="ml-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ресторан</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($categories as $category)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $category->name }}</td>
                <td class="px-6 py-4 text-gray-500 font-mono text-sm">{{ $category->slug }}</td>
                <td class="px-6 py-4 text-gray-500">{{ $category->restaurant?->name ?? 'N/A' }}</td>
                <td class="px-6 py-4">
                    @if($category->is_active)
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Активна</span>
                    @else
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Неактивна</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-900 mr-3">Ред.</a>
                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Удалить?')" class="text-red-600 hover:text-red-900">Удал.</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">Категории не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $categories->withQueryString()->links() }}
</div>
@endsection
