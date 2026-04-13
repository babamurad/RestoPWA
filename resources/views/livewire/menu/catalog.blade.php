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
}" x-init="$watch('showModal', value => { if(value) { $el.classList.add('overflow-hidden'); } else { $el.classList.remove('overflow-hidden'); } })">

    @if(!empty($categories))
        <div class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide px-4 md:px-0">
            <button 
                wire:click="filterByCategory(null)"
                class="px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all touch-feedback {{ $categoryId === null ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}"
            >
                Все
            </button>
            @foreach($categories as $category)
                <button 
                    wire:click="filterByCategory({{ $category['id'] }})"
                    class="px-5 py-2.5 rounded-2xl text-sm font-bold whitespace-nowrap transition-all touch-feedback {{ $categoryId == $category['id'] ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}"
                >
                    {{ $category['name'] }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="space-y-3" x-data="{ 
        cartItems: [],
        getQuantity(productId) {
            const item = this.cartItems.find(i => i.productId === productId);
            return item ? item.quantity : 0;
        }
    }" x-init="
        window.addEventListener('cart-state', (e) => {
            this.cartItems = e.detail.items || [];
        });
        $dispatch('request-cart-state');
    ">
        @foreach($products as $product)
            <div class="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all animate-slide-up card-hover">
                {{-- Product Image --}}
                <div class="relative flex-shrink-0 w-24 h-24 overflow-hidden rounded-xl bg-gray-50">
                    <img 
                        src="{{ $product['image'] }}" 
                        alt="{{ $product['name'] }}"
                        loading="lazy"
                        class="w-full h-full object-cover"
                    >
                    @if(!$product['is_available'])
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span class="text-white text-[10px] font-bold uppercase">Нет</span>
                        </div>
                    @endif
                </div>

                {{-- Product Details --}}
                <div class="flex flex-col flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-bold text-gray-900 line-clamp-1 text-sm md:text-base">{{ $product['name'] }}</h4>
                            @if($product['weight_g'])
                                <span class="text-[10px] md:text-xs text-gray-400 font-medium">{{ $product['weight_g'] }} г</span>
                            @endif
                        </div>
                    </div>
                    
                    <p class="mt-1 text-[11px] md:text-xs text-gray-500 line-clamp-2 leading-relaxed">
                        {{ $product['description'] }}
                    </p>

                    <div class="flex items-center justify-between mt-auto pt-2">
                        <div class="flex flex-col">
                            <span class="font-black text-gray-900 text-sm md:text-base">
                                {{ number_format($product['price'], 0, '.', ' ') }} ₽
                            </span>
                        </div>

                        {{-- Quantity Controls / Add Button --}}
                        <template x-if="getQuantity('{{ $product['id'] }}') > 0">
                            <div class="flex items-center gap-2.5 bg-gray-50 p-1 rounded-full border border-gray-100">
                                <button @click="$dispatch('cart-update-quantity', { itemId: cartItems.find(i => i.productId === '{{ $product['id'] }}').id, quantity: getQuantity('{{ $product['id'] }}') - 1 })" 
                                        class="flex items-center justify-center w-7 h-7 rounded-full bg-white text-gray-700 shadow-sm hover:bg-gray-100 transition-all touch-feedback">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                                </button>
                                <span class="w-4 text-center font-bold text-xs text-gray-900" x-text="getQuantity('{{ $product['id'] }}')"></span>
                                <button @click="$dispatch('cart-update-quantity', { itemId: cartItems.find(i => i.productId === '{{ $product['id'] }}').id, quantity: getQuantity('{{ $product['id'] }}') + 1 })" 
                                        class="flex items-center justify-center w-7 h-7 rounded-full bg-orange-500 text-white shadow-sm hover:bg-orange-600 transition-all touch-feedback">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="getQuantity('{{ $product['id'] }}') === 0">
                            <button 
                                wire:click="openModifierModal('{{ $product['id'] }}')"
                                dusk="open-modifier-modal-{{ $product['id'] }}"
                                @if(!$product['is_available']) disabled @endif
                                class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white shadow-lg shadow-orange-500/20 hover:bg-orange-600 transition-all touch-feedback disabled:opacity-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            </button>
                        </template>
                    </div>
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
                                                class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-orange-50/50 transition-colors"
                                                :class="isSelected({{ json_encode($modifier) }}) ? 'border-orange-500 bg-orange-50 text-orange-900' : 'border-gray-100'"
                                            >
                                                @if($modifier['type'] === 'single')
                                                    <input 
                                                        type="radio" 
                                                        :name="'modifier_' . $group"
                                                        class="w-4 h-4 text-orange-500 focus:ring-orange-500 border-gray-300"
                                                        @change="selectedModifiers = selectedModifiers.filter(m => m.group !== '{{ $group }}'); selectedModifiers.push({{ json_encode($modifier) }})"
                                                        :checked="isSelected({{ json_encode($modifier) }})"
                                                    >
                                                @else
                                                    <input 
                                                        type="checkbox" 
                                                        class="w-4 h-4 text-orange-500 rounded focus:ring-orange-500 border-gray-300"
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
                        class="w-full py-4 px-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-500/30 hover:bg-orange-600 transition-all active:scale-[0.98] flex items-center justify-center gap-2"
                    >
                        <span>Добавить за</span>
                        <span x-text="totalPrice.toLocaleString() + ' ₽'"></span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
