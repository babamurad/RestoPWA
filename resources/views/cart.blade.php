<x-layouts.app>
    <x-slot:title>Корзина - RestoPWA</x-slot:title>

    <div class="max-w-lg mx-auto bg-gray-50 min-h-screen shadow-xl relative pb-40" 
         x-data="{ 
            items: [],
            totalPrice: 0,
            totalQuantity: 0,
            isLoading: true,

            async init() {
                this.refresh();
                window.addEventListener('cart-state', (e) => {
                    this.items = e.detail.items;
                    this.totalPrice = e.detail.totalPrice;
                    this.totalQuantity = e.detail.totalQuantity;
                });
            },

            async refresh() {
                this.isLoading = true;
                const allItems = await window.CartService.getAllItems();
                this.items = allItems;
                const totals = await window.CartService.getTotals();
                this.totalPrice = totals.totalPrice;
                this.totalQuantity = totals.totalQuantity;
                this.isLoading = false;
            },

            async updateQuantity(itemId, quantity) {
                await window.CartService.updateQuantity(itemId, quantity);
                this.refresh();
            },

            async removeItem(itemId) {
                if(confirm('Удалить товар из корзины?')) {
                    await window.CartService.removeItem(itemId);
                    this.refresh();
                }
            }
         }">
        
        {{-- Header --}}
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50">
            <div class="flex items-center gap-3 px-4 h-14">
                <a href="{{ route('home') }}" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-lg font-bold text-gray-900 leading-none">Корзина</h1>
                
                <button @click="if(confirm('Очистить всю корзину?')) { await window.CartService.db.cart.clear(); refresh(); }" 
                        x-show="items.length > 0"
                        class="text-xs font-bold text-red-500 uppercase tracking-wider hover:bg-red-50 px-2 py-1 rounded-lg transition-all" x-cloak>
                    Очистить
                </button>
            </div>
        </header>

        <main class="px-4 py-6">
            
            {{-- Loading State --}}
            <template x-if="isLoading">
                <div class="flex flex-col items-center justify-center py-20">
                    <div class="w-10 h-10 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin"></div>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!isLoading && items.length === 0">
                <div class="flex flex-col items-center justify-center py-16 animate-slide-up">
                    <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-300"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900">Корзина пуста</h2>
                    <p class="text-gray-400 text-center mt-2 mb-8 max-w-[240px] leading-relaxed">Добавьте блюда из ресторанов, чтобы оформить заказ</p>
                    <a href="{{ route('home') }}" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-lg shadow-orange-500/30 transition-all touch-feedback active:scale-95">
                        К ресторанам
                    </a>
                </div>
            </template>

            {{-- Cart Items List --}}
            <template x-if="!isLoading && items.length > 0">
                <div class="space-y-4">
                    <template x-for="item in items" :key="item.id">
                        <div class="flex gap-4 p-4 bg-white rounded-3xl border border-gray-100 shadow-sm animate-slide-up group">
                            <div class="flex-shrink-0 w-24 h-24 overflow-hidden rounded-2xl bg-gray-50 border border-gray-50">
                                <img :src="item.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=200'" 
                                     class="w-full h-full object-cover">
                            </div>
                            
                            <div class="flex flex-col flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate leading-tight group-hover:text-orange-500 transition-colors" x-text="item.productName || 'Товар'"></h4>
                                        {{-- We don't have restaurant name in Dexie yet, we might need to add it or fetch it --}}
                                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none">Блюдо</span>
                                    </div>
                                    <button @click="removeItem(item.id)" class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all active:scale-90">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </div>
                                
                                <div class="flex items-center justify-between mt-auto pt-3">
                                    <span class="font-extrabold text-gray-900 text-lg" x-text="(item.price * item.quantity).toLocaleString() + ' ₽'"></span>
                                    
                                    <div class="flex items-center gap-3">
                                        <button @click="updateQuantity(item.id, item.quantity - 1)" 
                                                class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all touch-feedback active:scale-90">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                                        </button>
                                        <span class="w-6 text-center font-bold text-sm text-gray-900" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.id, item.quantity + 1)" 
                                                class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-500 text-white hover:bg-orange-600 shadow-sm shadow-orange-200 transition-all touch-feedback active:scale-90">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>

        {{-- Checkout Bar --}}
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-t border-gray-100 p-5 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]" 
             style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);"
             x-show="!isLoading && items.length > 0" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-y-full" x-transition:enter-end="transform translate-y-0">
            <div class="max-w-lg mx-auto">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex flex-col">
                        <span class="text-[10px] items-center font-bold text-gray-400 uppercase tracking-widest">Итого к оплате</span>
                        <span class="text-2xl font-black text-gray-900" x-text="totalPrice.toLocaleString() + ' ₽'"></span>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 text-orange-600" x-text="totalQuantity + ' поз.'"></span>
                    </div>
                </div>
                <button @click="$dispatch('cart-checkout')" class="w-full flex items-center justify-center gap-3 h-14 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/30 transition-all touch-feedback active:scale-95 group">
                    <span class="uppercase tracking-widest text-sm">Оформить заказ</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        {{-- Spacing per Bottom Nav if not at cart page --}}
        <div class="h-20"></div>
        <x-bottom-nav active="cart" />
    </div>
</x-layouts.app>
