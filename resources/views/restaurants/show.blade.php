<x-layouts.app>
    <x-slot:title>{{ $restaurant->name }} - Меню</x-slot:title>

    {{-- Header with back button --}}
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="flex items-center gap-3 px-4 h-14">
            <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="flex-1 text-lg font-bold text-gray-900 truncate mt-0.5 font-inter">{{ $restaurant->name }}</h1>
            
            <button class="flex items-center justify-center w-10 h-10 -mr-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
            </button>
        </div>
    </header>

    <main id="restaurant-content" class="pb-24" x-data="{ 
        activeCategory: 'all',
        isInfoOpen: false,
        cartTotal: 0,
        cartQuantity: 0,
        toggleInfo() { this.isInfoOpen = !this.isInfoOpen }
    }" x-init="
        $dispatch('set-vendor', { vendorId: '{{ $restaurant->id }}' });
        window.addEventListener('cart-state', (e) => {
            this.cartTotal = e.detail.totalPrice;
            this.cartQuantity = e.detail.totalQuantity;
        });
    ">
        
        {{-- Cover Image --}}
        <div class="relative h-48 overflow-hidden group">
            <img src="{{ $restaurant->cover_image ?? $restaurant->image_url }}" 
                    alt="{{ $restaurant->name }}" 
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        </div>

        {{-- Restaurant Info Card --}}
        <div class="px-4 -mt-6 relative z-10 font-inter">
            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-gray-200/40 border border-gray-100/50 animate-slide-up">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $restaurant->name }}</h2>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            @foreach($restaurant->categories->take(3) as $cat)
                                <span class="text-[10px] bg-orange-50 text-orange-600 px-2 py-0.5 rounded-lg font-bold uppercase tracking-wider">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <button @click="toggleInfo()" class="p-2.5 rounded-full bg-gray-50 text-gray-400 hover:bg-orange-50 hover:text-orange-500 transition-all touch-feedback"
                            :class="isInfoOpen ? 'bg-orange-500 text-white hover:bg-orange-600' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </button>
                </div>

                <div class="flex items-center gap-5 mt-5 text-[13px] font-bold text-gray-700">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#FF6B35" stroke="#FF6B35" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>{{ number_format($restaurant->rating, 1) }}</span>
                        <span class="text-gray-400 font-medium">({{ $restaurant->review_count }})</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>{{ $restaurant->delivery_time }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>{{ $restaurant->delivery_fee == 0 ? 'Бесплатно' : $restaurant->delivery_fee . ' ₽' }}</span>
                    </div>
                </div>

                {{-- Expanded Info Section --}}
                <div x-show="isInfoOpen" 
                        x-transition:enter="transition-all duration-300 ease-out"
                        x-transition:enter-start="opacity-0 max-h-0 pt-0"
                        x-transition:enter-end="opacity-100 max-h-[500px] pt-5"
                        class="mt-5 pt-5 border-t border-gray-100" x-cloak>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ $restaurant->description }}
                    </p>
                    <div class="mt-4 flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Минимальный заказ</span>
                        <span class="font-bold text-gray-900 text-sm">{{ number_format($restaurant->min_order, 0, '.', ' ') }} ₽</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menu Categories --}}
        <div class="mt-6 px-4">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
                <button @click="activeCategory = 'all'" 
                    class="px-5 py-2.5 rounded-full whitespace-nowrap text-sm font-bold transition-all duration-300 touch-feedback"
                    :class="activeCategory === 'all' 
                        ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' 
                        : 'bg-white text-gray-500 border border-gray-100 hover:border-orange-200'
                    ">
                    Все блюда
                </button>
                @foreach($restaurant->categories as $category)
                    <button @click="activeCategory = 'cat-{{ $category->id }}'" 
                        class="px-5 py-2.5 rounded-full whitespace-nowrap text-sm font-bold transition-all duration-300 touch-feedback"
                        :class="activeCategory === 'cat-{{ $category->id }}' 
                            ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' 
                            : 'bg-white text-gray-500 border border-gray-100 hover:border-orange-200'
                        ">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Menu Items --}}
        <div class="mt-4 px-4 pb-8 space-y-6">
            @foreach($restaurant->categories as $category)
                <section x-show="activeCategory === 'all' || activeCategory === 'cat-{{ $category->id }}'" 
                            class="animate-slide-up" x-cloak>
                    <div class="flex items-center gap-3 mb-4">
                        <h3 class="text-lg font-bold text-gray-900 font-inter">{{ $category->name }}</h3>
                        <div class="flex-1 h-[2px] bg-gray-100 rounded-full"></div>
                    </div>
                    
                    <div class="space-y-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
                        @foreach($category->products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

    </main>

    {{-- Bottom Action Bar (Floating Cart) --}}
    <div class="fixed bottom-4 left-0 right-0 z-50 p-4 pointer-events-none lg:hidden" x-show="cartQuantity > 0" x-cloak x-transition>
        <div class="max-w-lg mx-auto pointer-events-auto">
            <button @click="$dispatch('open-cart')" class="w-full flex items-center justify-between bg-orange-500 text-white h-16 px-6 rounded-2xl shadow-xl shadow-orange-500/40 hover:bg-orange-600 transition-all card-hover btn-press group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center font-bold text-sm" x-text="cartQuantity"></div>
                    <span class="font-bold text-sm uppercase tracking-widest">Просмотр заказа</span>
                </div>
                <span class="font-bold text-lg" x-text="cartTotal + ' ₽'"></span>
            </button>
        </div>
    </div>

    {{-- Bottom Navigation Spacing Fix --}}
    <div class="h-20 lg:hidden" x-show="cartQuantity === 0"></div>
    <x-bottom-nav active="restaurants" />

</x-layouts.app>
