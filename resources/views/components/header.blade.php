@props(['showSearch' => true, 'showProfile' => true])

<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
    <div class="max-w-lg mx-auto md:max-w-4xl lg:max-w-6xl xl:max-w-7xl flex items-center gap-2 sm:gap-3 px-3 sm:px-4 h-14 md:h-16">
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
            <div class="flex shrink-0 items-center justify-center w-8 h-8 md:w-10 md:h-10 bg-orange-500 rounded-lg shadow-md shadow-orange-200">
                <span class="text-white font-bold text-sm md:text-base tracking-tighter">R</span>
            </div>
            <span class="font-bold text-lg sm:text-xl md:text-2xl gradient-text tracking-tight shrink-0 hidden min-[360px]:block">RestoPWA</span>
        </div>
        
        @once
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('liveSearch', (initialQuery) => ({
                    search: initialQuery,
                    init() {
                        this.$watch('search', () => {
                            let val = this.search.trim().toLowerCase();
                            this.$dispatch('global-search', val);
                            
                            // Keep URL in sync
                            let url = new URL(window.location.href);
                            if(val.length > 0) {
                                url.searchParams.set('search', val);
                            } else {
                                url.searchParams.delete('search');
                            }
                            history.replaceState(null, '', url.toString() || '?');
                        });

                        // Dispatch initial search query on load
                        if (this.search) {
                            setTimeout(() => {
                                this.$dispatch('global-search', this.search.trim().toLowerCase());
                            }, 50);
                        }
                    }
                }));
            });
        </script>
        @endonce

        @if($showSearch)
            <div class="flex-1 max-w-xl mx-auto hidden md:block">
                <form action="{{ request()->routeIs('home') ? route('home') : route('restaurants.index') }}" 
                      method="GET" 
                      class="relative"
                      x-data="liveSearch('{{ request('search') }}')"
                      @submit.prevent>
                    <input type="text" 
                           name="search"
                           x-model="search"
                           placeholder="Найти блюдо, ресторан или категорию..."
                           class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none rounded-2xl text-sm focus:ring-2 focus:ring-orange-500 transition-all">
                    <svg class="absolute left-3 top-2.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </form>
            </div>
            
            <form action="{{ request()->routeIs('home') ? route('home') : route('restaurants.index') }}" 
                  method="GET" 
                  class="flex-1 md:hidden relative"
                  x-data="liveSearch('{{ request('search') }}')"
                  @submit.prevent>
                <input type="text" 
                       name="search"
                       x-model="search"
                       placeholder="Поиск"
                       class="w-full pl-8 pr-3 py-2 bg-gray-100 border-none rounded-full text-sm focus:ring-2 focus:ring-orange-500 transition-all">
                <svg class="absolute left-2.5 top-2.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </form>
        @endif

        <div class="flex items-center gap-2">
            @if($showProfile)
                @auth
                    @if(auth()->user()->is_admin)
                        <a href="/admin" class="hidden sm:flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-100 transition-colors" title="Панель администратора">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="9" x2="9" y1="21" y2="9"/></svg>
                        </a>
                    @elseif(auth()->user()->restaurant()->exists())
                        <a href="{{ route('vendor.orders.kanban') }}" class="hidden sm:flex items-center justify-center w-10 h-10 rounded-full hover:bg-orange-50 transition-colors" title="Панель ресторана">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="m3 9 2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.8 1.1L21 9"/><path d="M12 3v6"/></svg>
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-3 py-1.5 rounded-full hover:bg-gray-50 border border-transparent hover:border-gray-100 transition-all">
                        <div class="hidden md:block text-right">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Профиль</p>
                            <p class="text-sm font-bold text-gray-900 leading-none">{{ auth()->user()->name }}</p>
                        </div>
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-600 font-bold text-xs uppercase shadow-inner">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 sm:px-6 py-1.5 sm:py-2 shrink-0 bg-gray-900 text-white text-xs sm:text-sm font-bold rounded-xl hover:bg-black transition-all">
                        Войти
                    </a>
                @endauth
            @endif
        </div>
    </div>
</header>

<div class="flex items-center gap-2 px-4 py-2 bg-orange-50 border-b border-orange-100">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
    <span class="text-sm text-orange-700 truncate">{{ $address ?? 'ул. Ленина, 15, кв. 42' }}</span>
</div>