@extends('layouts.admin.app')

@section('title', 'Редактировать категорию')
@section('header', 'Редактирование категории')

@section('content')
<form action="{{ route('admin.categories.update', $category) }}" method="POST" class="max-w-2xl">
    @csrf @method('PUT')
    
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Название *</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('name') border-red-500 @enderror">
            @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('slug') border-red-500 @enderror">
            @error('slug')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ресторан *</label>
            <select name="restaurant_id" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500 @error('restaurant_id') border-red-500 @enderror">
                @foreach($restaurants as $r)
                <option value="{{ $r->id }}" {{ old('restaurant_id', $category->restaurant_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            @error('restaurant_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Родительская категория</label>
            <select name="parent_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                <option value="">-- Без родителя --</option>
                @foreach($parentCategories as $pc)
                <option value="{{ $pc->id }}" {{ old('parent_id', $category->parent_id) == $pc->id ? 'selected' : '' }}>{{ $pc->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">{{ old('description', $category->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Порядок сортировки</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                <span class="ml-2 text-sm text-gray-700">Активна</span>
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Отмена</a>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Сохранить</button>
        </div>
    </div>
</form>
@endsection
