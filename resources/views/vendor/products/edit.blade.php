@extends('vendor.layout.app')

@section('title', 'Редактировать товар')

@section('content')
<h2 class="text-2xl font-bold mb-6">Редактировать товар</h2>

<form method="POST" action="{{ route('vendor.products.update', $product) }}" enctype="multipart/form-data" class="max-w-2xl">
    @csrf
    @method('PUT')
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Основное</h3>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Название</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full border rounded px-3 py-2" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Описание</label>
            <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $product->description) }}</textarea>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Цена (₽)</label>
                <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" class="w-full border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Вес (г)</label>
                <input type="number" name="weight_g" value="{{ old('weight_g', $product->weight_g) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Категория</label>
            <select name="category_id" class="w-full border rounded px-3 py-2">
                <option value="">Выберите категорию</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Модификаторы</h3>
        
        <div id="modifiers-container">
            @forelse($product->modifiers ?? [] as $index => $modifier)
            <div class="modifier-group mb-4 p-4 border rounded">
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">Название модификатора</label>
                    <input type="text" name="modifiers[{{ $index }}][name]" value="{{ $modifier['name'] }}" class="w-full border rounded px-3 py-2">
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium mb-1">Тип</label>
                    <select name="modifiers[{{ $index }}][type]" class="w-full border rounded px-3 py-2">
                        <option value="radio" {{ ($modifier['type'] ?? '') === 'radio' ? 'selected' : '' }}>Радио (один выбор)</option>
                        <option value="checkbox" {{ ($modifier['type'] ?? '') === 'checkbox' ? 'selected' : '' }}>Чекбокс (множественный выбор)</option>
                        <option value="counter" {{ ($modifier['type'] ?? '') === 'counter' ? 'selected' : '' }}>Счетчик (количество)</option>
                    </select>
                </div>
                <div class="options-container">
                    <label class="block text-sm font-medium mb-1">Опции</label>
                    @forelse($modifier['options'] ?? [] as $optIndex => $option)
                    <div class="option-row flex gap-2 mb-2">
                        <input type="text" name="modifiers[{{ $index }}][options][{{ $optIndex }}][name]" value="{{ $option['name'] }}" placeholder="Название" class="flex-1 border rounded px-3 py-2">
                        <input type="number" name="modifiers[{{ $index }}][options][{{ $optIndex }}][price]" value="{{ $option['price'] ?? 0 }}" placeholder="Цена" step="0.01" class="w-24 border rounded px-3 py-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="modifiers[{{ $index }}][options][{{ $optIndex }}][is_default]" {{ ($option['is_default'] ?? false) ? 'checked' : '' }} class="mr-1"> По умолч.
                        </label>
                    </div>
                    @empty
                    <div class="option-row flex gap-2 mb-2">
                        <input type="text" name="modifiers[{{ $index }}][options][0][name]" placeholder="Название" class="flex-1 border rounded px-3 py-2">
                        <input type="number" name="modifiers[{{ $index }}][options][0][price]" placeholder="Цена" step="0.01" class="w-24 border rounded px-3 py-2">
                    </div>
                    @endforelse
                </div>
            </div>
            @empty
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
            </div>
            @endforelse
        </div>
        
        <button type="button" onclick="addModifier()" class="text-blue-600 hover:text-blue-800">+ Добавить модификатор</button>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold mb-4">Изображение и доступность</h3>
        
        @if($product->image)
        <div class="mb-4">
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded mb-2">
        </div>
        @endif
        
        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Изображение</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded px-3 py-2">
        </div>
        
        <div>
            <label class="flex items-center">
                <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} class="mr-2">
                Доступен для заказа
            </label>
        </div>
    </div>
    
    <div class="flex gap-4">
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">Сохранить</button>
        <a href="{{ route('vendor.products.index') }}" class="bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">Отмена</a>
    </div>
</form>

@push('scripts')
<script>
let modifierCount = {{ count($product->modifiers ?? [1 => []]) }};
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
