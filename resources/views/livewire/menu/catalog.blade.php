<div>

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
            const item = this.cartItems.find(i => i.productId == productId);
            if (item) console.log('Catalog: Quantity for ' + productId + ' is ' + item.quantity);
            return item ? item.quantity : 0;
        }
    }" x-init="
        window.addEventListener('cart-state', (e) => {
            console.log('Catalog: cart-state received', e.detail.items);
            this.cartItems = e.detail.items || [];
        });
        window.dispatchEvent(new CustomEvent('request-cart-state'));
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
                            <div class="flex items-center gap-2">
                                <button @click="$dispatch('cart-update-quantity', { itemId: cartItems.find(i => i.productId == '{{ $product['id'] }}').id, quantity: getQuantity('{{ $product['id'] }}') - 1 })" 
                                        class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors touch-feedback">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                                </button>
                                <span class="w-6 text-center font-semibold" x-text="getQuantity('{{ $product['id'] }}')"></span>
                                <button @click="$dispatch('cart-update-quantity', { itemId: cartItems.find(i => i.productId == '{{ $product['id'] }}').id, quantity: getQuantity('{{ $product['id'] }}') + 1 })" 
                                        class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 transition-colors touch-feedback">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="getQuantity('{{ $product['id'] }}') === 0">
                            <button 
                                wire:click="addDirectlyToCart('{{ $product['id'] }}')"
                                dusk="add-to-cart-{{ $product['id'] }}"
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


</div>
