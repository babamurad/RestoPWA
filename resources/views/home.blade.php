<x-layouts.app>
    <x-slot:title>RestoPWA - Доставка еды</x-slot:title>

    @if(!request()->is('*/desktop*'))
        {{-- Header + Location bar (mobile only) --}}
        <x-header />
    @endif

    <main class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl xl:max-w-7xl px-4 py-6 space-y-8 lg:py-10 pb-24 lg:pb-12">
        {{-- Categories --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 font-inter">Категории</h2>
            </div>
            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-3 md:gap-4 select-none"
                 x-data="{ active: null }">
                @foreach($categories as $category)
                    <button
                        @click="active = active === {{ $category->id }} ? null : {{ $category->id }}"
                        :class="active === {{ $category->id }}
                            ? 'bg-orange-500 text-white shadow-lg shadow-orange-200 ring-2 ring-orange-500 ring-offset-2'
                            : 'bg-white text-gray-700 border border-gray-100 hover:border-orange-500 hover:shadow-md'"
                        class="flex flex-col items-center gap-2 p-3 md:p-4 rounded-2xl transition-all touch-feedback group"
                    >
                        <div :class="active === {{ $category->id }} ? 'bg-white/20' : 'bg-gray-50 group-hover:bg-orange-50'" 
                             class="w-10 h-10 md:w-12 md:h-12 rounded-xl flex items-center justify-center transition-colors">
                            @if($category->icon)
                                <span class="text-xl md:text-2xl">{{ $category->icon }}</span>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>
                            @endif
                        </div>
                        <span class="text-[10px] md:text-xs font-bold whitespace-nowrap uppercase tracking-wider">{{ $category->name }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Популярные рестораны --}}
        @if($popularRestaurants->count())
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 font-inter">Популярные сейчас</h2>
                    <a href="{{ route('restaurants.index') }}" class="text-sm text-orange-500 font-bold hover:text-orange-600 transition-colors flex items-center gap-1">
                        Все
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                    @foreach($popularRestaurants as $restaurant)
                        <a href="{{ route('restaurants.show', $restaurant->slug) }}"
                           class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden card-hover touch-feedback group">
                            <div class="relative h-32 md:h-40 overflow-hidden">
                                <img src="{{ $restaurant->image_url }}"
                                     alt="{{ $restaurant->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                     loading="lazy">
                                <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-2 py-1 rounded-lg shadow-sm border border-gray-50">
                                    <div class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#FF6B35" stroke="#FF6B35" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        <span class="font-bold text-gray-900 text-[10px]">{{ $restaurant->rating }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 text-sm md:text-base truncate group-hover:text-orange-500 transition-colors">{{ $restaurant->name }}</h3>
                                <div class="flex items-center gap-3 mt-2 text-[11px] md:text-xs text-gray-400 font-medium font-inter">
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        {{ $restaurant->delivery_time }} мин
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                                        {{ $restaurant->delivery_fee == 0 ? 'Бесплатно' : $restaurant->delivery_fee . ' ₽' }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Все рестораны --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 font-inter">Рестораны рядом</h2>
                <button class="text-orange-500 p-2 md:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
                </button>
            </div>

            @if($restaurants->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($restaurants as $restaurant)
                        <x-restaurant-card :restaurant="$restaurant" />
                    @endforeach
                </div>

                @if($restaurants->hasPages())
                    <div class="mt-10 flex justify-center">
                        {{ $restaurants->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-20 text-gray-400 bg-white rounded-3xl border border-gray-100 shadow-sm">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="opacity-20 text-orange-500">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <p class="text-base font-medium font-inter">К сожалению, ничего не нашли</p>
                    <button class="mt-4 text-orange-500 font-bold hover:underline">Сбросить фильтры</button>
                </div>
            @endif
        </section>
    </main>

    {{-- Bottom Navigation --}}
    <x-bottom-nav active="home" />
</x-layouts.app>
