<x-layouts.app>
    <x-slot:title>Рестораны - RestoPWA</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-24">
        
        {{-- Header --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50 transition-all duration-300">
            <div class="flex items-center gap-3 px-4 h-14">
                <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-lg font-bold text-gray-900 truncate">Все рестораны</h1>
            </div>
            
            {{-- Search Bar --}}
            <div class="px-4 pb-3">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" 
                           id="search-restaurants"
                           placeholder="Поиск ресторанов..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-2xl text-sm font-medium placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-orange-100 focus:border-orange-200 transition-all">
                </div>
            </div>
        </header>

        <main class="px-4 py-4" x-data="{ 
            searchQuery: '',
            init() {
                document.getElementById('search-restaurants')?.addEventListener('input', (e) => {
                    this.searchQuery = e.target.value.toLowerCase();
                    this.filterRestaurants();
                });
            },
            filterRestaurants() {
                document.querySelectorAll('.restaurant-card').forEach(card => {
                    const name = card.dataset.name?.toLowerCase() || '';
                    const cuisine = card.dataset.cuisine?.toLowerCase() || '';
                    const match = name.includes(this.searchQuery) || cuisine.includes(this.searchQuery);
                    card.style.display = match ? '' : 'none';
                });
            }
        }">
            
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-500 font-medium">
                    Найдено: <span id="restaurants-count">{{ $restaurants->total() }}</span> ресторанов
                </p>
            </div>

            @if($restaurants->count())
                <div class="space-y-3" id="restaurants-list">
                    @foreach($restaurants as $restaurant)
                        <x-restaurant-card :restaurant="$restaurant" 
                                          class="restaurant-card" 
                                          :data-name="$restaurant->name"
                                          :data-cuisine="$restaurant->description ?? ''" />
                    @endforeach
                </div>

                @if($restaurants->hasPages())
                    <div class="mt-6">
                        {{ $restaurants->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-20 animate-slide-up">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-gray-900">Рестораны не найдены</h2>
                    <p class="text-gray-400 text-center mt-2 max-w-[260px] leading-relaxed text-sm">Попробуйте изменить параметры поиска</p>
                </div>
            @endif

        </main>

        <x-bottom-nav active="restaurants" />

    </div>
</x-layouts.app>
