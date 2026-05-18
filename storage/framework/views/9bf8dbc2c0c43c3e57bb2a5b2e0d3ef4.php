<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

     <?php $__env->slot('title', null, []); ?> Корзина - RestoPWA <?php $__env->endSlot(); ?>

    <div class="bg-gray-50 lg:bg-transparent min-h-screen lg:min-h-0">
        <div class="max-w-lg mx-auto lg:max-w-none bg-gray-50 lg:bg-transparent rounded-none lg:rounded-2xl shadow-none lg:shadow-sm lg:pb-8" 
             x-data="{ 
                items: [],
                totalPrice: 0,
                totalQuantity: 0,
                isLoading: true,
                isClearing: false,
                isSubmitting: false,
                hasMultiVendorConflict: false,

                async init() {
                    this.refresh();
                    window.addEventListener('cart-state', (e) => {
                        // Resiliency check: don't overwrite with empty items if totalQuantity shows we have contents
                        // This prevents the page from going blank due to vendor ID mismatches in the broadcasting manager
                        if (e.detail.items.length === 0 && e.detail.totalQuantity > 0 && this.items.length > 0) {
                            console.warn('cart.blade: Ignoring empty items broadcast because totals are > 0');
                            this.totalPrice = e.detail.totalPrice;
                            this.totalQuantity = e.detail.totalQuantity;
                            return;
                        }

                        this.items = [...e.detail.items];
                        this.totalPrice = e.detail.totalPrice;
                        this.totalQuantity = e.detail.totalQuantity;
                        this.hasMultiVendorConflict = e.detail.hasMultiVendorConflict || false;
                        this.isLoading = false;
                        this.isClearing = false;
                        this.isSubmitting = false;
                    });

                    window.addEventListener('submit-order-failed', (e) => {
                        this.isSubmitting = false;
                        if (this._submitTimeout) clearTimeout(this._submitTimeout);
                    });
                },

                get checkoutError() {
                    if (this.items.length === 0) return 'Корзина пуста';
                    if (this.hasMultiVendorConflict) return 'Разные рестораны';
                    return null;
                },

                _submitTimeout: null,

                async refresh() {
                    this.isLoading = true;
                    const allItems = await window.CartService.getAllItems();
                    this.items = allItems;
                    const totals = await window.CartService.getTotals();
                    this.totalPrice = totals.totalPrice;
                    this.totalQuantity = totals.totalQuantity;
                    this.isLoading = false;
                },

                _updatingId: null,

                async updateQuantity(itemId, quantity) {
                    if (this._updatingId === itemId) return;
                    this._updatingId = itemId;
                    try {
                        // Route through CartAlpine so broadcastState fires only once
                        window.dispatchEvent(new CustomEvent('cart-update-quantity', {
                            detail: { itemId, quantity }
                        }));
                    } finally {
                        setTimeout(() => { this._updatingId = null; }, 300);
                    }
                },

                async removeItem(itemId) {
                    const result = await Swal.fire({
                        title: 'Удалить товар?',
                        text: 'Позиция будет убрана из корзины',
                        icon: 'question',
                        showConfirmButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Удалить',
                        cancelButtonText: 'Отмена',
                        confirmButtonColor: '#f97316',
                        customClass: { popup: 'rounded-2xl' },
                    });
                    if (result.isConfirmed) {
                        // Route through CartAlpine so broadcastState fires only once
                        window.dispatchEvent(new CustomEvent('cart-remove-item', {
                            detail: { itemId }
                        }));
                    }
                },

                async confirmClear() {
                    const result = await Swal.fire({
                        title: 'Очистить корзину?',
                        text: 'Все товары будут удалены',
                        icon: 'warning',
                        showConfirmButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Очистить',
                        cancelButtonText: 'Отмена',
                        confirmButtonColor: '#ef4444',
                        customClass: { popup: 'rounded-2xl' },
                    });
                    if (result.isConfirmed) {
                        this.isClearing = true;
                        // Route through CartAlpine so broadcastState fires only once
                        window.dispatchEvent(new CustomEvent('cart-clear'));
                    }
                }
             }">
            
            
            <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-100/50">
                <div class="flex items-center gap-3 px-4 h-14">
                    <a href="<?php echo e(route('home')); ?>" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                    </a>
                    <h1 class="flex-1 text-lg font-bold text-gray-900 leading-none">Корзина</h1>
                    
                    <button @click="confirmClear()" 
                            x-show="items.length > 0"
                            :disabled="isClearing"
                            class="flex items-center gap-1.5 text-xs font-bold text-red-500 uppercase tracking-wider hover:bg-red-50 px-2 py-1 rounded-lg transition-all disabled:opacity-60 disabled:cursor-not-allowed" x-cloak>
                        <svg x-show="isClearing" class="animate-spin w-3.5 h-3.5 text-red-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isClearing ? 'Очистка...' : 'Очистить'"></span>
                    </button>
                </div>
            </header>

            <main class="px-4 pt-6" style="padding-bottom: calc(200px + 64px + env(safe-area-inset-bottom, 0px));">
                
                
                <template x-if="isLoading">
                    <div class="flex flex-col items-center justify-center py-20">
                        <div class="w-10 h-10 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin"></div>
                    </div>
                </template>

                
                <template x-if="!isLoading && items.length === 0">
                    <div class="flex flex-col items-center justify-center py-16 animate-slide-up">
                        <div class="w-24 h-24 bg-orange-50 rounded-full flex items-center justify-center mb-6 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-300"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        </div>
                        <h2 class="text-xl font-extrabold text-gray-900">Корзина пуста</h2>
                        <p class="text-gray-400 text-center mt-2 mb-8 max-w-[240px] leading-relaxed">Добавьте блюда из ресторанов, чтобы оформить заказ</p>
                        <a href="<?php echo e(route('home')); ?>" class="px-8 py-4 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-lg shadow-orange-500/30 transition-all touch-feedback active:scale-95">
                            К ресторанам
                        </a>
                    </div>
                </template>

                
                <template x-if="!isLoading && items.length > 0">
                    <div class="relative space-y-4 lg:grid lg:grid-cols-2 lg:gap-4 lg:space-y-0">

                        
                        <template x-if="isClearing">
                            <div class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 backdrop-blur-sm rounded-3xl">
                                <div class="w-10 h-10 border-4 border-red-200 border-t-red-500 rounded-full animate-spin"></div>
                                <p class="mt-3 text-sm font-semibold text-red-500">Очищаем корзину…</p>
                            </div>
                        </template>
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

            
            <div class="fixed left-0 right-0 z-40 bg-white/90 backdrop-blur-xl border-t border-gray-100 p-5 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]" 
                 style="bottom: calc(64px + env(safe-area-inset-bottom, 0px));"
                 x-show="!isLoading && items.length > 0" x-cloak 
                 x-transition:enter="transition ease-out duration-300" 
                 x-transition:enter-start="transform translate-y-full opacity-0" 
                 x-transition:enter-end="transform translate-y-0 opacity-100">
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
                    <button @click="isSubmitting = true; 
                                    setTimeout(() => { isSubmitting = false; }, 10000);
                                    $dispatch('cart-checkout');" 
                            dusk="cart-checkout-button" 
                            :disabled="isLoading || isSubmitting || items.length === 0 || hasMultiVendorConflict"
                            class="w-full flex items-center justify-center gap-3 h-14 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/30 transition-all touch-feedback active:scale-95 group disabled:opacity-70 disabled:cursor-not-allowed">
                        
                        <svg x-show="isSubmitting" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>

                        <span x-text="checkoutError || (isSubmitting ? 'Обработка...' : 'Оформить заказ')" class="uppercase tracking-widest text-sm"></span>
                        <svg x-show="!isSubmitting && !checkoutError" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            
            <?php if (isset($component)) { $__componentOriginal91530f48093dbfe9d7d6cfefc4ce84c1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91530f48093dbfe9d7d6cfefc4ce84c1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.bottom-nav','data' => ['active' => 'cart']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bottom-nav'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['active' => 'cart']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91530f48093dbfe9d7d6cfefc4ce84c1)): ?>
<?php $attributes = $__attributesOriginal91530f48093dbfe9d7d6cfefc4ce84c1; ?>
<?php unset($__attributesOriginal91530f48093dbfe9d7d6cfefc4ce84c1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91530f48093dbfe9d7d6cfefc4ce84c1)): ?>
<?php $component = $__componentOriginal91530f48093dbfe9d7d6cfefc4ce84c1; ?>
<?php unset($__componentOriginal91530f48093dbfe9d7d6cfefc4ce84c1); ?>
<?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH C:\OSPanel\domains\RestoPWA\resources\views/cart.blade.php ENDPATH**/ ?>