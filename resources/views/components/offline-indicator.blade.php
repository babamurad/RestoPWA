<div x-data="{ isOffline: !navigator.onLine }"
     x-init="window.addEventListener('online', () => isOffline = false);
             window.addEventListener('offline', () => isOffline = true)"
     x-show="isOffline"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="fixed top-0 left-0 right-0 z-[100] bg-amber-500 text-white py-2 px-4 shadow-lg flex items-center justify-center gap-3 animate-pulse"
     id="offline-indicator"
     x-cloak>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m19 19-4-4"/><path d="m5 5 4 4"/><path d="m5 19 4-4"/><path d="m19 5-4 4"/><path d="M12 12v.01"/></svg>
    <div class="flex items-center gap-2">
        <span class="text-xs font-black uppercase tracking-widest">Офлайн режим</span>
        <span class="hidden sm:inline-block text-[10px] font-bold opacity-80 border-l border-white/30 pl-2">Соединение потеряно, но корзина работает!</span>
    </div>
</div>
