<x-layouts.app>
    <x-slot:title>RestoPWA - Доставка еды</x-slot:title>

    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-xl relative">

        {{-- Header + Location bar --}}
        <x-header />

        <main class="px-4 py-4 space-y-6 pb-24">

            {{-- Categories --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Категории</h2>
                <div class="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2"
                     x-data="{ active: null }">
                    @foreach($categories as $category)
                        <button
                            @click="active = active === {{ $category->id }} ? null : {{ $category->id }}"
                            :class="active === {{ $category->id }}
                                ? 'bg-orange-500 text-white shadow-md'
                                : 'bg-white text-gray-700 border border-gray-200 hover:border-orange-300'"
                            class="flex-shrink-0 flex flex-col items-center gap-1.5 px-4 py-2.5 rounded-2xl transition-all touch-feedback min-w-[72px]"
                        >
                            @if($category->icon)
                                <span class="text-xl">{{ $category->icon }}</span>
                            @endif
                            <span class="text-xs font-medium whitespace-nowrap">{{ $category->name }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Популярные рестораны (горизонтальная прокрутка) --}}
            @if($popularRestaurants->count())
                <section>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-lg font-semibold text-gray-900">Популярные</h2>
                        <a href="{{ route('restaurants.index') }}" class="text-sm text-orange-500 font-medium hover:text-orange-600 transition-colors">Все</a>
                    </div>
                    <div class="flex gap-4 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-2">
                        @foreach($popularRestaurants as $restaurant)
                            <a href="{{ route('restaurants.show', $restaurant->slug) }}"
                               class="flex-shrink-0 w-44 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden card-hover touch-feedback">
                                <div class="relative h-28 overflow-hidden">
                                    <img src="{{ $restaurant->image_url }}"
                                         alt="{{ $restaurant->name }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                    @if(false) {{-- Placeholder for future promoted logic --}}
                                        <div class="absolute top-2 left-2 bg-orange-500 text-white text-xs font-medium px-2 py-0.5 rounded-full">
                                            Топ
                                        </div>
                                    @endif
                                </div>
                                <div class="p-2.5">
                                    <h3 class="font-semibold text-gray-900 text-sm truncate">{{ $restaurant->name }}</h3>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            <span class="font-medium text-gray-700">{{ $restaurant->rating }}</span>
                                        </div>
                                        <span>{{ $restaurant->delivery_time }} мин</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Все рестораны (вертикальный список) --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Рядом с вами</h2>

                @if($restaurants->count())
                    <div class="space-y-3">
                        @foreach($restaurants as $restaurant)
                            <x-restaurant-card :restaurant="$restaurant" />
                        @endforeach
                    </div>

                    {{-- Пагинация --}}
                    @if($restaurants->hasPages())
                        <div class="mt-4">
                            {{ $restaurants->links() }}
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        <p class="text-sm">Рестораны не найдены</p>
                    </div>
                @endif
            </section>

        </main>

        {{-- Bottom Navigation --}}
        <x-bottom-nav active="home" />

    </div>
</x-layouts.app>
