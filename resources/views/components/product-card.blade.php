@props(['product'])

<div class="flex lg:block gap-3 p-3 bg-white rounded-2xl border border-gray-100 shadow-sm transition-all hover:border-orange-100 group animate-slide-up">
    <div class="relative flex-shrink-0 w-24 h-24 lg:w-full lg:h-40 overflow-hidden rounded-xl bg-gray-100">
        <img src="{{ $product->image_url }}" 
             alt="{{ $product->name }}" 
             class="w-full h-full object-cover grayscale-[0.2] group-hover:grayscale-0 transition-all duration-300" 
             loading="lazy">
        
        {{-- Badge for highlights/popular (example logic) --}}
        @if(method_exists($product, 'isPopular') && $product->isPopular())
            <div class="absolute top-1 left-1 flex items-center gap-1 px-1.5 py-0.5 bg-orange-500 text-white text-[10px] font-semibold rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-2.072-2.143-3-4-.928 1.857-1.928 1.857-3 4-.5 1-1 1.62-1 3a2.5 2.5 0 0 0 4.5 1.5A2.5 2.5 0 0 0 12 12a2.5 2.5 0 0 0 4.5 1.5A2.5 2.5 0 0 0 21 12c0-1.38-.5-2-1-3-1.072-2.143-2.072-2.143-3-4-.928 1.857-1.928 1.857-3 4-.5 1-1 1.62-1 3a2.5 2.5 0 0 0 2.5 2.5Z"/></svg>
                Хит
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 min-w-0 lg:mt-3">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h4 class="font-semibold text-gray-900 truncate leading-tight group-hover:text-orange-600 transition-colors">{{ $product->name }}</h4>
                @if($product->weight_g)
                    <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $product->weight_g }} г</span>
                @endif
            </div>
        </div>
        
        <p class="mt-1 text-xs text-gray-500 line-clamp-2 leading-relaxed">
            {{ $product->description }}
        </p>

        <div class="flex items-center justify-between mt-auto pt-2">
            <div class="flex flex-col">
                <span class="font-bold text-gray-900 text-base leading-none">{{ number_format($product->price, 0, '.', ' ') }} ₽</span>
            </div>

            {{-- Cart Control (Alpine.js integration) --}}
            <div x-data="{ 
                count: 0,
                itemId: null,
                init() {
                    window.addEventListener('cart-state', (e) => {
                        const item = e.detail.items.find(i => i.productId === '{{ $product->id }}');
                        this.count = item ? item.quantity : 0;
                        this.itemId = item ? item.id : null;
                    });
                },
                add() {
                    window.dispatchEvent(new CustomEvent('cart-add-item', { 
                        detail: { 
                            productId: '{{ $product->id }}', 
                            vendorId: '{{ $product->vendor_id }}', 
                            price: {{ $product->price }},
                            productName: '{{ $product->name }}',
                            image: '{{ $product->image_url }}'
                        } 
                    }));
                },
                update(delta) {
                    if (this.itemId) {
                        window.dispatchEvent(new CustomEvent('cart-update-quantity', { 
                            detail: { 
                                itemId: this.itemId, 
                                quantity: this.count + delta 
                            } 
                        }));
                    }
                }
            }" class="flex items-center">
                <div x-show="count > 0" class="flex items-center gap-2 animate-scale-in" x-cloak>
                    <button @click="update(-1)" 
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all touch-feedback active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                    </button>
                    <span class="w-6 text-center font-bold text-sm text-gray-900" x-text="count"></span>
                    <button @click="update(1)" 
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 shadow-sm shadow-orange-200 transition-all touch-feedback active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    </button>
                </div>
                
                <button x-show="count === 0" @click="add()" 
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 shadow-sm shadow-orange-200 transition-all touch-feedback active:scale-95 animate-scale-in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>