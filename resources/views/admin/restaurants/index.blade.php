@extends('layouts.admin.app')

@section('title', 'Рестораны')
@section('header', 'Управление ресторанами')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex-1 max-w-lg">
        <form method="GET" action="{{ route('admin.restaurants.index') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Поиск по названию..." 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            <select name="status" class="px-4 py-2 border rounded-lg">
                <option value="">Все статусы</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Активные</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Неактивные</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Фильтр</button>
        </form>
    </div>
    <a href="{{ route('admin.restaurants.create') }}" class="ml-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        + Добавить ресторан
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($restaurants as $restaurant)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-medium">{{ $restaurant->name }}</td>
                <td class="px-6 py-4 text-gray-500 font-mono text-sm">{{ $restaurant->slug }}</td>
                <td class="px-6 py-4">
                    @if($restaurant->is_active)
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Активен</span>
                    @else
                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Неактивен</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.restaurants.edit', $restaurant) }}" class="text-blue-600 hover:text-blue-900 mr-3">Редактировать</a>
                    <form action="{{ route('admin.restaurants.destroy', $restaurant) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Удалить ресторан?')" class="text-red-600 hover:text-red-900">Удалить</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">Рестораны не найдены</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $restaurants->withQueryString()->links() }}
</div>
@endsection
