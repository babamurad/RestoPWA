<div x-data="{
    showModal: @entangle('selectedProductId'),
    selectedModifiers: @entangle('selectedModifiers'),
    quantity: @entangle('selectedQuantity'),
    basePrice: @entangle('selectedProductPrice'),
    productName: @entangle('selectedProductName'),
    get totalPrice() {
        let price = this.basePrice;
        this.selectedModifiers.forEach(mod => {
            if (mod.price) price += parseInt(mod.price);
        });
        return price * this.quantity;
    },
    toggleModifier(modifier) {
        if (modifier.type === 'single') {
            this.selectedModifiers = this.selectedModifiers.filter(m => m.group !== modifier.group);
            this.selectedModifiers.push(modifier);
        } else {
            const exists = this.selectedModifiers.find(m => m.id === modifier.id);
            if (exists) {
                this.selectedModifiers = this.selectedModifiers.filter(m => m.id !== modifier.id);
            } else {
                this.selectedModifiers.push(modifier);
            }
        }
    },
    isSelected(modifier) {
        if (modifier.type === 'single') {
            return this.selectedModifiers.some(m => m.group === modifier.group && m.id === modifier.id);
        }
        return this.selectedModifiers.some(m => m.id === modifier.id);
    }
}" x-init="$watch('selectedProductId', value => { if(value) { $el.classList.add('overflow-hidden'); } else { $el.classList.remove('overflow-hidden'); } })">

    @if(!empty($categories))
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
            <button 
                wire:click="filterByCategory(null)"
                class="px-4 py-2 rounded-full text-sm whitespace-nowrap transition-colors {{ $categoryId === null ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
            >
                Все
            </button>
            @foreach($categories as $category)
                <button 
                    wire:click="filterByCategory({{ $category['id'] }})"
                    class="px-4 py-2 rounded-full text-sm whitespace-nowrap transition-colors {{ $categoryId == $category['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    {{ $category['name'] }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($products as $product)
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                <div class="aspect-square bg-gray-100 relative">
                    @if($product['image'])
                        <img 
                            src="{{ $product['image'] }}" 
                            alt="{{ $product['name'] }}"
                            loading="lazy"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                    @if(!$product['is_available'])
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span class="text-white text-sm font-medium">Нет в наличии</span>
                        </div>
                    @endif
                </div>
                <div class="p-3">
                    <h3 class="font-medium text-gray-900 text-sm line-clamp-2 mb-1">{{ $product['name'] }}</h3>
                    <div class="flex items-center justify-between">
                        <span class="text-blue-600 font-semibold">
                            @if($product['min_price'] > $product['price'])
                                от {{ number_format($product['min_price'], 0, '.', ' ') }} ₽
                            @else
                                {{ number_format($product['price'], 0, '.', ' ') }} ₽
                            @endif
                        </span>
                    </div>
                    <button 
                        wire:click="openModifierModal('{{ $product['id'] }}')"
                        dusk="open-modifier-modal-{{ $product['id'] }}"
                        @if(!$product['is_available']) disabled @endif
                        class="mt-2 w-full py-2 px-3 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        В корзину
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($hasMorePages)
        <div class="mt-6 text-center" x-data="{}" x-intersect="$wire.loadMore()">
            <button wire:click="loadMore" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                Загрузить еще
            </button>
        </div>
    @endif

    <div 
        x-show="showModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/50" @click="showModal = ''"></div>
        
        <div 
            x-show="showModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="relative bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
        >
            @php
                $selectedProduct = null;
                if (!empty($selectedProductId)) {
                    foreach ($products as $p) {
                        if ($p['id'] === $selectedProductId) {
                            $selectedProduct = $p;
                            break;
                        }
                    }
                }
            @endphp
            
            @if($selectedProduct)
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ $selectedProduct['name'] }}</h3>
                    <button @click="showModal = ''" class="p-1 hover:bg-gray-100 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if(!empty($selectedProduct['modifiers']))
                        @foreach($selectedProduct['modifiers'] as $group => $modifiers)
                            @if(is_array($modifiers) && !empty($modifiers))
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-2">
                                        {{ $modifiers[0]['group_name'] ?? $group }}
                                        @if(($modifiers[0]['required'] ?? false) === true)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </h4>
                                    <div class="space-y-2">
                                        @foreach($modifiers as $modifier)
                                            <label 
                                                class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                                :class="isSelected({{ json_encode($modifier) }}) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                                            >
                                                @if($modifier['type'] === 'single')
                                                    <input 
                                                        type="radio" 
                                                        :name="'modifier_' . $group"
                                                        class="w-4 h-4 text-blue-600"
                                                        @change="selectedModifiers = selectedModifiers.filter(m => m.group !== '{{ $group }}'); selectedModifiers.push({{ json_encode($modifier) }})"
                                                        :checked="isSelected({{ json_encode($modifier) }})"
                                                    >
                                                @else
                                                    <input 
                                                        type="checkbox" 
                                                        class="w-4 h-4 text-blue-600 rounded"
                                                        @change="toggleModifier({{ json_encode($modifier) }})"
                                                        :checked="isSelected({{ json_encode($modifier) }})"
                                                    >
                                                @endif
                                                <span class="flex-1 ml-3">{{ $modifier['name'] }}</span>
                                                @if(($modifier['price'] ?? 0) > 0)
                                                    <span class="text-gray-500">+{{ number_format($modifier['price'], 0, '.', ' ') }} ₽</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-4">Нет дополнительных опций</p>
                    @endif

                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Количество</h4>
                        <div class="flex items-center gap-3">
                            <button 
                                @click="quantity = Math.max(1, quantity - 1)"
                                class="w-10 h-10 flex items-center justify-center border rounded-lg hover:bg-gray-50"
                            >
                                -
                            </button>
                            <span x-text="quantity" class="w-12 text-center font-medium"></span>
                            <button 
                                @click="quantity++"
                                class="w-10 h-10 flex items-center justify-center border rounded-lg hover:bg-gray-50"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white border-t px-6 py-4">
                    <button 
                        wire:click="addToCart"
                        dusk="add-to-cart-submit"
                        class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                    >
                        <span>Добавить</span>
                        <span x-text="totalPrice.toLocaleString() + ' ₽'"></span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
