@props(['active' => 'home'])

<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
        <a href="{{ route('home') }}" class="relative flex flex-col items-center justify-center w-full h-full {{ $active === 'home' ? 'text-orange-500' : 'text-gray-400 hover:text-gray-600' }} transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span class="mt-1 text-xs font-medium">Главная</span>
            @if($active === 'home')
                <div class="absolute top-0 w-12 h-0.5 bg-orange-500 rounded-b-full"></div>
            @endif
        </a>
        <a href="{{ route('orders.index') }}" class="relative flex flex-col items-center justify-center w-full h-full {{ $active === 'orders' ? 'text-orange-500' : 'text-gray-400 hover:text-gray-600' }} transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span class="mt-1 text-xs font-medium">Заказы</span>
            @if($active === 'orders')
                <div class="absolute top-0 w-12 h-0.5 bg-orange-500 rounded-b-full"></div>
            @endif
        </a>
        <a href="{{ route('cart') }}" 
           class="relative flex flex-col items-center justify-center w-full h-full {{ $active === 'cart' ? 'text-orange-500' : 'text-gray-400 hover:text-gray-600' }} transition-colors"
           x-data="cartButton('')">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <span x-show="badgeCount > 0" 
                      x-text="badgeCount" 
                      class="absolute -top-2 -right-2 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-orange-500 rounded-full animate-pulse shadow-sm shadow-orange-500/50" 
                      x-cloak></span>
            </div>
            <span class="mt-1 text-xs font-medium">Корзина</span>
            @if($active === 'cart')
                <div class="absolute top-0 w-12 h-0.5 bg-orange-500 rounded-b-full"></div>
            @endif
        </a>
        <a href="{{ route('profile.edit') }}" class="relative flex flex-col items-center justify-center w-full h-full {{ $active === 'profile' ? 'text-orange-500' : 'text-gray-400 hover:text-gray-600' }} transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="mt-1 text-xs font-medium">Профиль</span>
            @if($active === 'profile')
                <div class="absolute top-0 w-12 h-0.5 bg-orange-500 rounded-b-full"></div>
            @endif
        </a>
    </div>
</nav>