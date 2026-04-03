<div x-data="{ 
         isOffline: !navigator.onLine,
         type: 'network',
         checking: false,
         async reconnect() {
            this.checking = true;
            if (window.checkConnectivity) {
                await window.checkConnectivity();
            }
            setTimeout(() => this.checking = false, 1000);
         }
     }"
     x-init="window.addEventListener('connectivity-changed', (e) => {
                 isOffline = e.detail.isOffline;
                 type = e.detail.type;
             })"
     x-show="isOffline"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="fixed top-0 left-0 right-0 z-[100] bg-zinc-900 border-b border-white/10 text-white py-2.5 px-4 shadow-2xl flex items-center justify-between gap-3"
     id="offline-indicator"
     x-cloak>
    
    <div class="flex items-center gap-3">
        <div class="relative flex items-center justify-center">
            <div class="absolute inset-0 bg-amber-500/20 rounded-full animate-ping"></div>
            <div class="relative w-2 h-2 rounded-full bg-amber-500"></div>
        </div>
        
        <div class="flex flex-col">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 leading-none mb-0.5" x-text="type === 'network' ? 'Нет интернета' : 'Сервер недоступен'">Офлайн режим</span>
            <span class="text-[9px] font-bold text-white/50 leading-none">Корзина и меню доступны локально</span>
        </div>
    </div>

    <button @click="reconnect" 
            :disabled="checking"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 active:bg-white/5 rounded-full transition-all border border-white/10 disabled:opacity-50">
        <svg x-show="!checking" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-white"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
        <svg x-show="checking" class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        <span class="text-[10px] font-black uppercase tracking-wider" x-text="checking ? '...' : 'Обновить'">Обновить</span>
    </button>
</div>
