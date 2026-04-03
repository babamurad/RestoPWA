<x-layouts.app>
    <x-slot:title>{{ $restaurant->name }} - Меню</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-24">
        
        {{-- Header with back button --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50 transition-all duration-300">
            <div class="flex items-center gap-3 px-4 h-14">
                <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-lg font-bold text-gray-900 truncate leading-none mt-0.5">{{ $restaurant->name }}</h1>
                
                <button class="flex items-center justify-center w-10 h-10 -mr-2 rounded-full hover:bg-gray-100 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 font-bold"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                </button>
            </div>
        </header>

        <main id="restaurant-content" x-data="{ 
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
            
            {{-- Cover Image & Gradient --}}
            <div class="relative h-56 overflow-hidden group">
                <img src="{{ $restaurant->cover_image ?? $restaurant->image_url }}" 
                     alt="{{ $restaurant->name }}" 
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                
                @if(method_exists($restaurant, 'isPromoted') && $restaurant->isPromoted())
                    <div class="absolute top-4 left-4 bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg uppercase tracking-widest shadow-lg shadow-orange-500/30">
                        Popular
                    </div>
                @endif
            </div>

            {{-- Restaurant Info Card --}}
            <div class="px-4 -mt-10 relative z-10">
                <div class="bg-white rounded-2xl p-5 shadow-xl shadow-gray-200/50 border border-gray-100/50 animate-slide-up">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 pr-2">
                            <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $restaurant->name }}</h2>
                            <div class="flex flex-wrap items-center gap-1 mt-1.5 min-w-0">
                                @foreach($restaurant->categories->take(3) as $cat)
                                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider">{{ $cat->name }}</span>
                                @endforeach
                                @if($restaurant->categories->count() > 3)
                                    <span class="text-[10px] text-gray-400 font-medium">+{{ $restaurant->categories->count() - 3 }}</span>
                                @endif
                            </div>
                        </div>
                        <button @click="toggleInfo()" class="flex-shrink-0 p-2.5 rounded-full bg-gray-50 text-gray-400 hover:bg-orange-50 hover:text-orange-500 transition-all transition-colors touch-feedback active:scale-95"
                                :class="isInfoOpen ? 'bg-orange-500 text-white hover:bg-orange-600' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-5 mt-5 text-[13px] font-semibold text-gray-600">
                        <div class="flex items-center gap-1.5 group">
                            <div class="w-6 h-6 flex items-center justify-center rounded-lg bg-amber-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <span class="text-gray-900">{{ number_format($restaurant->rating, 1) }}</span>
                            <span class="text-gray-400 font-medium">({{ $restaurant->review_count }}+)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 flex items-center justify-center rounded-lg bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <span class="text-gray-900">{{ $restaurant->delivery_time }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 flex items-center justify-center rounded-lg bg-green-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <span class="text-gray-900">{{ $restaurant->delivery_fee == 0 ? 'Бесплатно' : $restaurant->delivery_fee . ' ₽' }}</span>
                        </div>
                    </div>

                    {{-- Expanded Info Section --}}
                    <div x-show="isInfoOpen" 
                         x-transition:enter="transition-all duration-300 ease-out"
                         x-transition:enter-start="opacity-0 max-h-0 pt-0"
                         x-transition:enter-end="opacity-100 max-h-[500px] pt-5"
                         class="mt-5 pt-5 border-t border-gray-100" x-cloak>
                        <p class="text-sm text-gray-500 leading-relaxed italic">
                            &ldquo;{{ $restaurant->description }}&rdquo;
                        </p>
                        <div class="mt-4 flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Минимальный заказ</span>
                            <span class="font-bold text-gray-900 text-sm">{{ number_format($restaurant->min_order, 0, '.', ' ') }} ₽</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Menu Categories (Sticky horizontal scroll) --}}
            <div class="sticky top-14 z-30 bg-gray-50/95 backdrop-blur-md pt-6 pb-2 border-b border-transparent transition-all duration-300" 
                 :class="{ 'border-gray-200/50 shadow-sm': activeCategory !== 'all' }">
                <div class="flex gap-2.5 overflow-x-auto scrollbar-hide px-4 pb-2">
                    <button @click="activeCategory = 'all'" 
                        class="px-5 py-2.5 rounded-2xl whitespace-nowrap text-sm font-bold transition-all duration-300 touch-feedback shadow-sm"
                        :class="activeCategory === 'all' 
                            ? 'bg-gray-900 text-white shadow-gray-400/30' 
                            : 'bg-white text-gray-500 border border-gray-100 hover:border-gray-200'
                        ">
                        Все блюда
                    </button>
                    @foreach($restaurant->categories as $category)
                        <button @click="activeCategory = 'cat-{{ $category->id }}'" 
                            class="px-5 py-2.5 rounded-2xl whitespace-nowrap text-sm font-bold transition-all duration-300 touch-feedback shadow-sm"
                            :class="activeCategory === 'cat-{{ $category->id }}' 
                                ? 'bg-orange-500 text-white shadow-orange-500/30' 
                                : 'bg-white text-gray-500 border border-gray-100 hover:border-gray-200'
                            ">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Menu Items --}}
            <div class="mt-4 px-4 pb-8 space-y-8">
                @foreach($restaurant->categories as $category)
                    <section x-show="activeCategory === 'all' || activeCategory === 'cat-{{ $category->id }}'" 
                             class="animate-slide-up" x-cloak>
                        <div class="flex items-center gap-3 mb-4">
                            <h3 class="text-lg font-bold text-gray-900">{{ $category->name }}</h3>
                            <div class="flex-1 h-px bg-gradient-to-r from-gray-200 to-transparent"></div>
                            <span class="text-xs font-bold text-gray-300 uppercase tracking-widest">{{ $category->products->count() }}</span>
                        </div>
                        
                        <div class="space-y-3">
                            @foreach($category->products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

        </main>

        {{-- Bottom Action Bar (Floating Cart) --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 p-4 pointer-events-none" x-show="cartQuantity > 0" x-cloak x-transition>
            <div class="max-w-lg mx-auto pointer-events-auto">
                <a href="{{ route('cart') }}" class="flex items-center justify-between bg-orange-500 text-white h-14 px-6 rounded-2xl shadow-xl shadow-orange-500/40 hover:bg-orange-600 transition-all card-hover group active:scale-95">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center font-bold text-sm" x-text="cartQuantity"></div>
                        <span class="font-bold text-sm uppercase tracking-wider">Перейти в корзину</span>
                    </div>
                    <span class="font-bold text-lg" x-text="cartTotal + ' ₽'"></span>
                </a>
            </div>
        </div>

    </div>

    {{-- Bottom Navigation Spacing Fix --}}
    <div class="h-20"></div>
    <x-bottom-nav active="restaurants" />

</x-layouts.app>
