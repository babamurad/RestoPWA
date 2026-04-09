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

    <main id="restaurant-content" class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl md:py-10 pb-24" x-data="{ 
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
        
        {{-- Cover Image (Desktop Grid Layout) --}}
        <div class="md:grid md:grid-cols-3 md:gap-8 px-4">
            <div class="col-span-2">
                <div class="relative h-48 md:h-64 rounded-3xl overflow-hidden shadow-lg group">
                    <img src="{{ $restaurant->cover_image ?? $restaurant->image_url }}" 
                            alt="{{ $restaurant->name }}" 
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-white hidden md:block">
                        <h2 class="text-4xl font-bold leading-tight">{{ $restaurant->name }}</h2>
                        <div class="flex items-center gap-4 mt-3">
                            <div class="flex items-center gap-1.5 bg-white/20 backdrop-blur-md px-3 py-1 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#FFB800" stroke="#FFB800" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                <span class="font-bold">{{ number_format($restaurant->rating, 1) }}</span>
                            </div>
                            <span class="text-sm font-medium opacity-90">{{ $restaurant->review_count }} отзывов</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Info Card (Desktop Only) --}}
            <div class="hidden md:block col-span-1">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 h-full">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Информация</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Доставка</p>
                                <p class="font-bold text-gray-900">{{ $restaurant->delivery_time }} мин</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </div>
                            <div>
                                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Стоимость</p>
                                <p class="font-bold text-gray-900">{{ $restaurant->delivery_fee == 0 ? 'Бесплатно' : $restaurant->delivery_fee . ' ₽' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-50">
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $restaurant->description }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Info Card (Visible on mobile only) --}}
        <div class="px-4 -mt-6 relative z-10 font-inter md:hidden">
            <div class="bg-white rounded-2xl p-5 shadow-lg shadow-gray-200/40 border border-gray-100/50">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 leading-tight">{{ $restaurant->name }}</h2>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            @foreach($restaurant->categories->take(3) as $cat)
                                <span class="text-[10px] bg-orange-50 text-orange-600 px-2 py-0.5 rounded-lg font-bold uppercase tracking-wider">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <button @click="toggleInfo()" class="p-2.5 rounded-full bg-gray-50 text-gray-400 hover:bg-orange-50 hover:text-orange-500 transition-all touch-feedback">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </button>
                </div>

                <div class="flex items-center gap-5 mt-5 text-[13px] font-bold text-gray-700">
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#FF6B35" stroke="#FF6B35" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>{{ number_format($restaurant->rating, 1) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>{{ $restaurant->delivery_time }}</span>
                    </div>
                </div>

                <div x-show="isInfoOpen" class="mt-5 pt-5 border-t border-gray-100" x-cloak x-transition>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $restaurant->description }}</p>
                </div>
            </div>
        </div>

        <div class="md:grid md:grid-cols-4 md:gap-8 mt-8 px-4">
            {{-- Left: Desktop Categories Sidebar --}}
            <div class="hidden md:block col-span-1">
                <div class="sticky top-24 space-y-2">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest pl-4 mb-4">Меню ресторана</h3>
                    <button @click="activeCategory = 'all'" 
                            :class="activeCategory === 'all' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                            class="w-full text-left px-5 py-3 rounded-xl font-bold transition-all flex items-center justify-between group">
                        <span>Все блюда</span>
                        <svg class="opacity-40 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    @foreach($restaurant->categories as $category)
                        <button @click="activeCategory = 'cat-{{ $category->id }}'" 
                                :class="activeCategory === 'cat-{{ $category->id }}' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                                class="w-full text-left px-5 py-3 rounded-xl font-bold transition-all flex items-center justify-between group">
                            <span>{{ $category->name }}</span>
                            <svg class="opacity-40 group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Right: Menu Items Grid --}}
            <div class="col-span-3">
                <livewire:menu.catalog :vendor-id="$restaurant->id" />
            </div>

        </div>

    </main>

    {{-- Floating Cart Button --}}
    <div class="fixed bottom-6 left-0 right-0 z-50 p-4 pointer-events-none" x-show="cartQuantity > 0" x-cloak x-transition>
        <div class="max-w-lg mx-auto pointer-events-auto">
            <button @click="$dispatch('open-cart')" class="w-full flex items-center justify-between bg-orange-500 text-white h-16 px-8 rounded-2xl shadow-xl shadow-orange-500/40 hover:bg-orange-600 transition-all card-hover btn-press group">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold" x-text="cartQuantity"></div>
                    <span class="font-bold text-sm uppercase tracking-widest hidden md:inline">Ваш заказ</span>
                    <span class="font-bold text-sm uppercase tracking-widest md:hidden">Заказ</span>
                </div>
                <span class="font-bold text-xl" x-text="cartTotal + ' ₽'"></span>
            </button>
        </div>
    </div>

    <x-bottom-nav active="restaurants" />

</x-layouts.app>
