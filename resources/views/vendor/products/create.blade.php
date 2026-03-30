@extends('vendor.layout.app')

@section('title', 'Добавить товар')

@section('content')
<h2 class="text-2xl font-bold mb-6">Добавить товар</h2>

<form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data" class="max-w-2xl">
    @csrf
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Основное</h3>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Название</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Описание</label>
            <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Цена (₽)</label>
                <input type="number" name="price" value="{{ old('price') }}" step="0.01" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Вес (г)</label>
                <input type="number" name="weight_g" value="{{ old('weight_g') }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Категория</label>
            <select name="category_id" class="w-full border rounded px-3 py-2">
                <option value="">Выберите категорию</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Модификаторы</h3>
        
        <div id="modifiers-container">
            <div class="modifier-group mb-4 p-4 border rounded">
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">Название модификатора</label>
                    <input type="text" name="modifiers[0][name]" class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">Тип</label>
                    <select name="modifiers[0][type]" class="w-full border rounded px-3 py-2">
                        <option value="radio">Радио (один выбор)</option>
                        <option value="checkbox">Чекбокс (множественный выбор)</option>
                        <option value="counter">Счетчик (количество)</option>
                    </select>
                </div>
                <div class="options-container">
                    <label class="block text-sm font-medium mb-1">Опции</label>
                    <div class="option-row flex gap-2 mb-2">
                        <input type="text" name="modifiers[0][options][0][name]" placeholder="Название" class="flex-1 border rounded px-3 py-2">
                        <input type="number" name="modifiers[0][options][0][price]" placeholder="Цена" step="0.01" class="w-24 border rounded px-3 py-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="modifiers[0][options][0][is_default]" class="mr-1"> По умолч.
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="button" onclick="addModifier()" class="text-blue-600 hover:text-blue-800">+ Добавить модификатор</button>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Изображение и доступность</h3>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Изображение</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_available" value="1" checked class="mr-2">
                Доступен для заказа
            </label>
        </div>
    </div>
    
    <div class="flex gap-4">
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">Создать</button>
        <a href="{{ route('vendor.products.index') }}" class="bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">Отмена</a>
    </div>
</form>

@push('scripts')
<script>
let modifierCount = 1;
function addModifier() {
    const html = `
        <div class="modifier-group mb-4 p-4 border rounded">
            <div class="mb-2">
                <label class="block text-sm font-medium mb-1">Название модификатора</label>
                <input type="text" name="modifiers[${modifierCount}][name]" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-2">
                <label class="block text-sm font-medium mb-1">Тип</label>
                <select name="modifiers[${modifierCount}][type]" class="w-full border rounded px-3 py-2">
                    <option value="radio">Радио (один выбор)</option>
                    <option value="checkbox">Чекбокс (множественный выбор)</option>
                    <option value="counter">Счетчик (количество)</option>
                </select>
            </div>
            <div class="options-container">
                <label class="block text-sm font-medium mb-1">Опции</label>
                <div class="option-row flex gap-2 mb-2">
                    <input type="text" name="modifiers[${modifierCount}][options][0][name]" placeholder="Название" class="flex-1 border rounded px-3 py-2">
                    <input type="number" name="modifiers[${modifierCount}][options][0][price]" placeholder="Цена" step="0.01" class="w-24 border rounded px-3 py-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="modifiers[${modifierCount}][options][0][is_default]" class="mr-1"> По умолч.
                    </label>
                </div>
            </div>
        </div>
    `;
    document.getElementById('modifiers-container').insertAdjacentHTML('beforeend', html);
    modifierCount++;
}
</script>
@endpush
@endsection
