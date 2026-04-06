<x-layouts.app>
    <x-slot:title>RestoPWA - Доставка еды</x-slot:title>

    @if(!request()->is('*/desktop*'))
        {{-- Header + Location bar (mobile only) --}}
        <x-header />
    @endif

    <main class="px-4 py-4 space-y-6 pb-24 lg:pb-8">
        {{-- Categories --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-3 font-inter">Категории</h2>
            <div class="flex gap-2.5 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2"
                 x-data="{ active: null }">
                @foreach($categories as $category)
                    <button
                        @click="active = active === {{ $category->id }} ? null : {{ $category->id }}"
                        :class="active === {{ $category->id }}
                            ? 'bg-orange-500 text-white shadow-lg shadow-orange-200'
                            : 'bg-white text-gray-700 border border-gray-100 hover:border-orange-200'"
                        class="flex-shrink-0 flex flex-col items-center gap-1.5 px-4 py-3 rounded-2xl transition-all touch-feedback min-w-[80px] group"
                    >
                        <div :class="active === {{ $category->id }} ? 'bg-white/20' : 'bg-gray-50 group-hover:bg-orange-50'" 
                             class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors">
                            @if($category->icon)
                                <span class="text-2xl">{{ $category->icon }}</span>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>
                            @endif
                        </div>
                        <span class="text-[11px] font-bold whitespace-nowrap uppercase tracking-wider">{{ $category->name }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Популярные рестораны --}}
        @if($popularRestaurants->count())
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold text-gray-900 font-inter">Популярные</h2>
                    <a href="{{ route('restaurants.index') }}" class="text-sm text-orange-500 font-bold hover:text-orange-600 transition-colors">Все</a>
                </div>
                <div class="flex gap-4 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
                    @foreach($popularRestaurants as $restaurant)
                        <a href="{{ route('restaurants.show', $restaurant->slug) }}"
                           class="flex-shrink-0 w-48 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden card-hover touch-feedback group">
                            <div class="relative h-32 overflow-hidden">
                                <img src="{{ $restaurant->image_url }}"
                                     alt="{{ $restaurant->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                     loading="lazy">
                                <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm p-1.5 rounded-lg shadow-sm">
                                    <div class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#FF6B35" stroke="#FF6B35" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        <span class="font-bold text-gray-900 text-[10px]">{{ $restaurant->rating }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <h3 class="font-bold text-gray-900 text-sm truncate">{{ $restaurant->name }}</h3>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-400 font-medium font-inter">
                                    <span>{{ $restaurant->delivery_time }} мин</span>
                                    <span>•</span>
                                    <span>{{ $restaurant->delivery_fee == 0 ? 'Бесплатно' : $restaurant->delivery_fee . ' ₽' }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Все рестораны --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900 mb-3 font-inter">Рядом с вами</h2>

            @if($restaurants->count())
                <div class="space-y-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
                    @foreach($restaurants as $restaurant)
                        <x-restaurant-card :restaurant="$restaurant" />
                    @endforeach
                </div>

                @if($restaurants->hasPages())
                    <div class="mt-8">
                        {{ $restaurants->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-16 text-gray-400 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3 opacity-20">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <p class="text-sm font-medium font-inter">Рестораны не найдены</p>
                </div>
            @endif
        </section>
    </main>

    {{-- Bottom Navigation --}}
    <x-bottom-nav active="home" />
</x-layouts.app>
