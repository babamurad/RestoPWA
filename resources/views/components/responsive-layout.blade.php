@props(['active' => 'home'])

<div class="min-h-screen bg-gray-50">
    {{-- Desktop Sidebar (lg+) --}}
    <aside class="hidden lg:flex lg:flex-col lg:fixed lg:left-0 lg:top-0 lg:bottom-0 lg:w-64 lg:bg-white lg:border-r lg:border-gray-100 lg:z-50">
        <div class="flex items-center gap-3 px-6 h-16 border-b border-gray-100">
            <div class="flex items-center justify-center w-10 h-10 bg-orange-500 rounded-xl">
                <span class="text-white font-bold text-lg">R</span>
            </div>
            <span class="font-bold text-2xl bg-gradient-to-r from-orange-500 to-orange-400 bg-clip-text text-transparent">RestoPWA</span>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="{{ route('home') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ $active === 'home' ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span class="font-semibold">Главная</span>
            </a>
            
            <a href="{{ route('restaurants.index') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ $active === 'restaurants' ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="font-semibold">Рестораны</span>
            </a>
            
            <a href="{{ route('orders.index') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ $active === 'orders' ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                <span class="font-semibold">Мои заказы</span>
            </a>
            
            <a href="{{ route('cart') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ $active === 'cart' ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50' }}" x-data="cartButton('')">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    <span x-show="badgeCount > 0" x-text="badgeCount" class="absolute -top-2 -right-2 flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-orange-500 rounded-full animate-pulse shadow-sm shadow-orange-500/50" x-cloak></span>
                </div>
                <span class="font-semibold">Корзина</span>
            </a>
            
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all {{ $active === 'profile' ? 'bg-orange-50 text-orange-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="font-semibold">Профиль</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-100">
            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl">
                @auth
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-orange-600 font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="w-full px-4 py-2 bg-orange-500 text-white font-semibold rounded-xl text-center hover:bg-orange-600 transition-colors">
                        Войти
                    </a>
                @endauth
            </div>
        </div>
    </aside>
    
    {{-- Main Content Area --}}
    <div class="lg:lg:pl-64">
        {{-- Desktop Header (lg+) --}}
        <header class="hidden lg:block sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
            <div class="flex items-center justify-between px-8 h-16">
                <div class="flex items-center gap-6 w-full max-w-xl">
                    <form action="{{ route('restaurants.index') }}" method="GET" class="w-full relative">
                        <input type="text" 
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Поиск ресторанов и блюд..."
                               class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border-none rounded-full text-sm font-medium focus:ring-2 focus:ring-orange-500 hover:bg-gray-100 transition-colors">
                        <svg class="absolute left-4 top-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </form>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-full text-orange-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span class="text-sm font-medium">{{ $address ?? 'ул. Ленина, 15, кв. 42' }}</span>
                    </div>
                </div>
            </div>
        </header>
        
        {{-- Mobile App Container (< lg) --}}
        <div class="lg:hidden">
            {{ $slot }}
        </div>
        
        {{-- Desktop Content Container (lg+) --}}
        <div class="hidden lg:block px-8 py-8">
            <div class="max-w-5xl mx-auto">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
