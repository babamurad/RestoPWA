<div x-data="{
    currentStep: <?php if ((object) ('currentStep') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('currentStep'->value()); ?>')<?php echo e('currentStep'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('currentStep'); ?>')<?php endif; ?>,
    isProcessing: <?php if ((object) ('isProcessing') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isProcessing'->value()); ?>')<?php echo e('isProcessing'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isProcessing'); ?>')<?php endif; ?>,
    error: <?php if ((object) ('error') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('error'->value()); ?>')<?php echo e('error'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('error'); ?>')<?php endif; ?>,
    createdOrder: <?php if ((object) ('createdOrder') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('createdOrder'->value()); ?>')<?php echo e('createdOrder'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('createdOrder'); ?>')<?php endif; ?>,
    isOffline: <?php if ((object) ('isOffline') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOffline'->value()); ?>')<?php echo e('isOffline'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOffline'); ?>')<?php endif; ?>,
    address: <?php if ((object) ('address') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('address'->value()); ?>')<?php echo e('address'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('address'); ?>')<?php endif; ?>,
    deliveryTime: <?php if ((object) ('deliveryTime') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('deliveryTime'->value()); ?>')<?php echo e('deliveryTime'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('deliveryTime'); ?>')<?php endif; ?>,
    isAsap: <?php if ((object) ('isAsap') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isAsap'->value()); ?>')<?php echo e('isAsap'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isAsap'); ?>')<?php endif; ?>,
    paymentMethod: <?php if ((object) ('paymentMethod') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('paymentMethod'->value()); ?>')<?php echo e('paymentMethod'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('paymentMethod'); ?>')<?php endif; ?>,
    comment: <?php if ((object) ('comment') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('comment'->value()); ?>')<?php echo e('comment'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('comment'); ?>')<?php endif; ?>,
    cartItems: <?php if ((object) ('cartItems') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('cartItems'->value()); ?>')<?php echo e('cartItems'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('cartItems'); ?>')<?php endif; ?>,
    cartTotal: <?php if ((object) ('cartTotal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('cartTotal'->value()); ?>')<?php echo e('cartTotal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('cartTotal'); ?>')<?php endif; ?>,
    deliveryFee: <?php if ((object) ('deliveryFee') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('deliveryFee'->value()); ?>')<?php echo e('deliveryFee'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('deliveryFee'); ?>')<?php endif; ?>,
    finalTotal: <?php if ((object) ('finalTotal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('finalTotal'->value()); ?>')<?php echo e('finalTotal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('finalTotal'); ?>')<?php endif; ?>,
    priceChanges: <?php if ((object) ('priceChanges') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('priceChanges'->value()); ?>')<?php echo e('priceChanges'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('priceChanges'); ?>')<?php endif; ?>,
    unavailableItems: <?php if ((object) ('unavailableItems') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('unavailableItems'->value()); ?>')<?php echo e('unavailableItems'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('unavailableItems'); ?>')<?php endif; ?>,
    conflictsConfirmed: <?php echo \Illuminate\Support\Js::from(empty($priceChanges) && empty($unavailableItems))->toHtml() ?>,
    isSyncing: false,
    multiVendorConflict: false,
    multiVendorIds: [],
    
    async init() {
        if (this.cartItems.length === 0) {
            const vendorId = '<?php echo e($vendorId); ?>';
            const items = await window.CartService.getCartByVendor(vendorId);
            this.cartItems = items;
            this.$wire.set('cartItems', items);
            this.$wire.calculateTotals();
        }

        this.checkMultiVendorConflict();

        window.addEventListener('connectivity-changed', (e) => {
            this.isOffline = e.detail.isOffline;
        });

        window.addEventListener('submit-order-failed', (e) => {
            const detail = e.detail || {};
            const reason = detail.reason || 'validation';
            const messages = {
                multi_vendor: 'В корзине товары из разных ресторанов. Очистите корзину или перейдите к выбору ресторана.',
                empty_cart: 'Корзина пуста. Добавьте товары для оформления заказа.',
                no_vendor: 'Ресторан не выбран. Вернитесь в меню и выберите ресторан.',
                network: 'Проблема с подключением. Проверьте интернет и повторите.',
                validation: detail.message || 'Проверьте правильность заполнения полей.',
            };
            this.error = messages[reason] || detail.message || 'Произошла ошибка. Попробуйте ещё раз.';
            console.warn('[CheckoutWizard] submit-order-failed:', reason, detail);
        });
    },

    async checkMultiVendorConflict() {
        const allItems = await window.CartService.getAllItems();
        const vendorIds = [...new Set(allItems.map(item => String(item.vendorId)))];
        if (vendorIds.length > 1) {
            this.multiVendorConflict = true;
            this.multiVendorIds = vendorIds;
        } else {
            this.multiVendorConflict = false;
            this.multiVendorIds = [];
        }
    },

    async handleClearCartAndContinue() {
        if (confirm('Очистить корзину и продолжить с текущим рестораном?')) {
            await window.CartService.clearAllCarts();
            this.multiVendorConflict = false;
            this.error = null;
            window.location.reload();
        }
    },

    handleGoToRestaurantSelection() {
        window.location.href = '<?php echo e(route('restaurants.index')); ?>';
    },

    async handleNext() {
        if (this.currentStep === 3) {
            this.isSyncing = true;
            try {
                const vendorId = '<?php echo e($vendorId); ?>';
                const syncData = await window.CartService.syncWithServer(vendorId, this.cartItems);
                
                if (syncData) {
                    this.$wire.setConflicts(syncData.price_changes, syncData.unavailable_items);
                    
                    const updatedItems = await window.CartService.getCartByVendor(vendorId);
                    this.cartItems = updatedItems;
                    this.$wire.updateCartData(updatedItems, syncData.subtotal);
                    
                    if (syncData.price_changes.length > 0 || syncData.unavailable_items.length > 0) {
                        this.conflictsConfirmed = false;
                    } else {
                        this.conflictsConfirmed = true;
                    }
                }
            } catch (e) {
                this.$wire.set('error', 'Ошибка синхронизации с сервером. Проверьте соединение.');
                return;
            } finally {
                this.isSyncing = false;
            }
        }
        this.$wire.nextStep();
    },
}" dusk="checkout-wizard" class="max-w-lg mx-auto bg-white min-h-screen relative font-inter">

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($createdOrder): ?>
        <div class="px-6 py-12 text-center animate-slide-up">
            <div class="w-20 h-20 bg-green-50 rounded-3xl flex items-center justify-center mx-auto mb-6 text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                <?php echo e($isOffline ? 'Заказ сохранен!' : 'Заказ оформлен!'); ?>

            </h2>
            
            <p class="text-gray-500 mb-8 leading-relaxed px-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isOffline): ?>
                    Мы сохранили ваш заказ локально. Он будет отправлен в ресторан автоматически, как только восстановится соединение.
                <?php else: ?>
                    Ваш заказ #<?php echo e($createdOrder->id); ?> успешно принят и уже передается в ресторан.
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>

            <div class="space-y-3">
                <a href="<?php echo e(route('order.track', $createdOrder->id ?? 0)); ?>" 
                    class="block w-full py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-600 transition-all btn-press">
                    Отследить заказ
                </a>
                <a href="<?php echo e(route('home')); ?>" 
                    class="block w-full py-4 bg-gray-50 text-gray-600 font-bold rounded-2xl hover:bg-gray-100 transition-all btn-press">
                    На главную
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100">
            <div class="flex items-center gap-3 px-4 h-14">
                <button onclick="history.back()" class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-gray-100 transition-all touch-feedback active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <h1 class="flex-1 text-lg font-bold text-gray-900 mt-0.5">Оформление</h1>
                
                
                <div class="flex gap-1.5 pr-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($i = 1; $i <= 5; $i++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="w-2 h-2 rounded-full transition-all duration-300 <?php echo e($currentStep >= $i ? 'bg-orange-500' : 'bg-gray-200'); ?>"></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 space-y-6 pb-32">
            
            <template x-if="multiVendorConflict">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3 animate-shake">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 text-amber-600 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-amber-800">В корзине товары из разных ресторанов</p>
                            <p class="text-xs text-amber-600 mt-1">Оформить заказ можно только из одного ресторана за раз.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="handleClearCartAndContinue()"
                            class="flex-1 py-2.5 bg-amber-500 text-white text-sm font-bold rounded-xl hover:bg-amber-600 transition-all">
                            Очистить и продолжить
                        </button>
                        <button @click="handleGoToRestaurantSelection()"
                            class="flex-1 py-2.5 bg-white text-amber-700 text-sm font-bold rounded-xl border border-amber-200 hover:bg-amber-50 transition-all">
                            Выбрать ресторан
                        </button>
                    </div>
                </div>
            </template>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error): ?>
                <div class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 text-sm font-medium flex items-center gap-3 animate-shake">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><?php echo e($error); ?></span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php switch($currentStep):
                case (1): ?>
                    
                    <section class="space-y-4 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">Адрес доставки</h3>
                            <button wire:click="$dispatch('open-address-selector')" class="text-sm font-bold text-orange-500 hover:text-orange-600">Изменить</button>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['address']) || !empty($address['lat'])): ?>
                            <div class="p-4 bg-orange-50 rounded-2xl border border-orange-100 space-y-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['address'])): ?>
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-orange-500 shadow-sm shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 truncate"><?php echo e($address['address']); ?></p>
                                            <p class="text-xs text-orange-400 font-medium mt-0.5">Адрес доставки</p>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['manual_address'])): ?>
                                    <p class="text-sm text-gray-700 ml-[52px]"><?php echo e($address['manual_address']); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['landmark']) || !empty($address['entrance']) || !empty($address['floor']) || !empty($address['apartment'])): ?>
                                    <div class="flex flex-wrap gap-2 ml-[52px]">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['landmark'])): ?><span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100"><svg class="w-3 h-3 mr-1 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg><?php echo e($address['landmark']); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['entrance'])): ?><span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">п. <?php echo e($address['entrance']); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['floor'])): ?><span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">эт. <?php echo e($address['floor']); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['apartment'])): ?><span class="inline-flex items-center px-2.5 py-1 bg-white rounded-lg text-xs font-medium text-gray-700 border border-orange-100">кв. <?php echo e($address['apartment']); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($address['courier_comment'])): ?>
                                    <p class="text-xs text-gray-500 italic ml-[52px]">«<?php echo e($address['courier_comment']); ?>»</p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button wire:click="$dispatch('open-address-selector')" 
                                class="w-full p-8 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center gap-3 text-gray-400 hover:border-orange-200 hover:text-orange-500 transition-all group">
                                <div class="w-12 h-12 rounded-full bg-gray-50 group-hover:bg-orange-50 flex items-center justify-center transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                </div>
                                <span class="font-bold text-sm">Выбрать адрес на карте</span>
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </section>
                    <?php break; ?>

                <?php case (2): ?>
                    
                    <section class="space-y-4 animate-slide-up">
                        <h3 class="text-lg font-bold text-gray-900">Время доставки</h3>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <button wire:click="$set('isAsap', true)" 
                                class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all <?php echo e($isAsap ? 'border-orange-500 bg-orange-50' : 'border-gray-50 hover:bg-gray-50'); ?>">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo e($isAsap ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-gray-900">Как можно скорее</p>
                                    <p class="text-xs text-gray-400 font-medium">Примерно 35–50 мин</p>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isAsap): ?>
                                    <div class="ml-auto text-orange-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </button>

                            <button wire:click="$set('isAsap', false)" 
                                class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all <?php echo e(!$isAsap ? 'border-orange-500 bg-orange-50' : 'border-gray-50 hover:bg-gray-50'); ?>">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center <?php echo e(!$isAsap ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400'); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                                <div class="text-left flex-1">
                                    <p class="font-bold text-gray-900">Выбрать время</p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isAsap): ?>
                                        <input type="time" wire:model="deliveryTime" class="mt-2 block w-full bg-white border-gray-200 rounded-xl text-sm font-bold focus:ring-orange-500 focus:border-orange-500">
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </button>
                        </div>
                    </section>
                    <?php break; ?>

                <?php case (3): ?>
                    
                    <section class="space-y-6 animate-slide-up">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900"><?php echo e(__('checkout.summary.contacts')); ?></h3>
                        </div>

                        <div class="space-y-4">
                            
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-700"><?php echo e(__('checkout.summary.name_label')); ?></label>
                                <input type="text" wire:model.live="name" name="name" placeholder="Иван Иванов"
                                    class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all <?php echo e($nameError ? 'ring-2 ring-red-300 bg-red-50' : ''); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nameError): ?>
                                    <p class="text-xs text-red-500 font-medium px-2"><?php echo e($nameError); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-700"><?php echo e(__('checkout.summary.phone_label')); ?></label>
                                <input type="tel" wire:model.live="phone"
                                    <?php if($phoneMode === 'strict_region'): ?> x-mask="+\9\9399999999" <?php endif; ?>
                                    placeholder="<?php echo e($phoneExample); ?>"
                                    class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all <?php echo e($phoneError ? 'ring-2 ring-red-300 bg-red-50' : ''); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($phoneError): ?>
                                    <p class="text-xs text-red-500 font-medium px-2"><?php echo e($phoneError); ?></p>
                                <?php else: ?>
                                    <p class="text-[10px] text-gray-400 font-medium px-2"><?php echo e($phoneHelperText); ?>. <?php echo e(__('checkout.summary.comment_placeholder')); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <div class="pt-4">
                            <label class="block text-sm font-bold text-gray-900 mb-2"><?php echo e(__('checkout.summary.comment_label')); ?></label>
                            <textarea wire:model.live="comment"
                                x-on:input="$wire.set('comment', $event.target.value)"
                                placeholder="<?php echo e(__('checkout.summary.comment_placeholder')); ?>"
                                class="w-full p-4 bg-gray-50 border-none rounded-2xl text-sm font-medium focus:ring-2 focus:ring-orange-500/20 focus:bg-white transition-all"
                                rows="3"
                                maxlength="<?php echo e(\App\Support\PhoneNormalizer::maxCommentLength()); ?>"></textarea>
                            <p class="text-[10px] text-gray-400 font-medium px-2 mt-1" x-text="'<?php echo e(__('checkout.validation.comment.too_long', ['max' => \App\Support\PhoneNormalizer::maxCommentLength()])); ?>'"></p>
                        </div>
                    </section>
                    <?php break; ?>

                <?php case (4): ?>
                    
                    <section class="space-y-6 animate-slide-up">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Проверка корзины</h3>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($priceChanges) && empty($unavailableItems)): ?>
                            <div class="p-8 text-center bg-green-50 rounded-3xl border border-green-100">
                                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 text-green-500 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <p class="font-bold text-green-800">Все товары доступны!</p>
                                <p class="text-sm text-green-600 mt-1">Цены актуальны, можно продолжать.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($priceChanges)): ?>
                                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                        <div class="flex items-center gap-2 mb-3 text-amber-700 font-bold text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                            <span>Изменение цен</span>
                                        </div>
                                        <div class="bg-white rounded-xl overflow-hidden border border-amber-100 divide-y divide-gray-50">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $priceChanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $change): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <div class="p-3 flex justify-between items-center text-sm">
                                                    <span class="text-gray-600 font-medium"><?php echo e($change['name']); ?></span>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-400 line-through"><?php echo e(number_format($change['old_price'], 0, '.', ' ')); ?></span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="M5 12h14l-7-7M12 19l7-7"/></svg>
                                                        <span class="font-bold text-gray-900"><?php echo e(number_format($change['new_price'], 0, '.', ' ')); ?> ₽</span>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($unavailableItems)): ?>
                                    <div class="p-4 bg-red-50 rounded-2xl border border-red-100">
                                        <div class="flex items-center gap-2 mb-3 text-red-700 font-bold text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                            <span>Недоступные товары</span>
                                        </div>
                                        <div class="space-y-2">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $unavailableItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <div class="flex items-center gap-3 p-2 bg-white/50 rounded-lg">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="font-bold text-gray-900 text-sm truncate"><?php echo e($uItem['name']); ?></p>
                                                        <p class="text-[10px] text-red-500 font-bold uppercase"><?php echo e($uItem['reason']); ?></p>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                        <p class="text-xs text-red-400 mt-3 font-medium italic">Эти товары были удалены из вашего заказа автоматически.</p>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <div class="pt-2">
                                    <button x-on:click="conflictsConfirmed = true" 
                                        class="w-full py-4 rounded-2xl font-bold transition-all flex items-center justify-center gap-3"
                                        :class="conflictsConfirmed ? 'bg-green-500 text-white' : 'bg-orange-100 text-orange-600 hover:bg-orange-200'">
                                        <template x-if="conflictsConfirmed">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </template>
                                        <span x-text="conflictsConfirmed ? 'Изменения приняты' : 'Принять изменения и продолжить'"></span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="flex gap-3">
                            <button @click="window.location.href = '<?php echo e(route('restaurants.show', $vendorId)); ?>'" 
                                class="flex-1 py-4 bg-gray-50 text-gray-500 font-bold rounded-2xl hover:bg-gray-100 transition-all">
                                Вернуться в меню
                            </button>
                        </div>
                    </section>
                    <?php break; ?>

                <?php case (5): ?>
                    
                    <section class="space-y-6 animate-slide-up">
                        <h3 class="text-lg font-bold text-gray-900">Подтверждение заказа</h3>
                        
                        
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Доставка по адресу</p>
                                    <p class="font-bold text-gray-900 text-sm truncate"><?php echo e($address['address'] ?? 'Не выбран'); ?></p>
                                </div>
                                <button wire:click="goToStep(1)" class="text-xs font-bold text-orange-500">Изм.</button>
                            </div>
                            
                            <div class="h-px bg-gray-50"></div>

                            <div class="flex items-start gap-12">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Время</p>
                                        <p class="font-bold text-gray-900 text-sm"><?php echo e($isAsap ? 'Как можно скорее' : $deliveryTime); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-green-50 text-green-500 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Оплата</p>
                                        <p class="font-bold text-gray-900 text-sm uppercase"><?php echo e($paymentMethod); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="space-y-3">
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest">Ваш заказ</h4>
                            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden divide-y divide-gray-50">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="p-3 flex items-center gap-3">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item['image'] ?? false): ?>
                                            <img src="<?php echo e($item['image']); ?>" class="w-12 h-12 rounded-lg object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg bg-gray-50 flex items-center justify-center text-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/></svg>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate"><?php echo e($item['productName']); ?></p>
                                            <p class="text-xs text-gray-400 font-medium">x<?php echo e($item['quantity']); ?></p>
                                        </div>
                                        <p class="font-bold text-gray-900 text-sm"><?php echo e(number_format($item['price'] * $item['quantity'], 0, '.', ' ')); ?> ₽</p>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="bg-gray-50 rounded-2xl p-4 space-y-2">
                            <div class="flex justify-between text-sm text-gray-500 font-medium">
                                <span>Сумма заказа</span>
                                <span><?php echo e(number_format($cartTotal, 0, '.', ' ')); ?> ₽</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 font-medium">
                                <span>Доставка</span>
                                <span class="<?php echo e($deliveryFee == 0 ? 'text-green-500' : ''); ?> font-bold">
                                    <?php echo e($deliveryFee == 0 ? 'Бесплатно' : number_format($deliveryFee, 0, '.', ' ') . ' ₽'); ?>

                                </span>
                            </div>
                            <div class="h-px bg-gray-200 my-2"></div>
                            <div class="flex justify-between text-gray-900">
                                <span class="text-lg font-bold">Итого</span>
                                <span class="text-2xl font-black"><?php echo e(number_format($finalTotal, 0, '.', ' ')); ?> ₽</span>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isOffline): ?>
                            <div class="p-4 bg-amber-50 border border-amber-100 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </div>
                                <p class="text-xs font-bold text-amber-700">Офлайн режим: заказ будет отправлен автоматически при входе в сеть.</p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </section>
                    <?php break; ?>
            <?php endswitch; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </main>

        
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 pb-screen-safe">
            <div class="max-w-lg mx-auto space-y-3">
                
                <template x-if="error && currentStep === 5">
                    <div class="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 text-xs font-medium flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span x-text="error"></span>
                    </div>
                </template>

                <div class="flex gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentStep > 1): ?>
                        <button wire:click="prevStep" 
                            class="px-6 py-4 bg-gray-50 text-gray-400 font-bold rounded-2xl hover:bg-gray-100 transition-all btn-press">
                            Назад
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentStep < 5): ?>
                        <button @click="handleNext()" :disabled="isSyncing || (currentStep === 4 && !conflictsConfirmed)"
                            class="flex-1 px-6 py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-lg shadow-orange-200 hover:bg-orange-600 transition-all btn-press flex items-center justify-center gap-3 disabled:opacity-50">
                            <span x-show="!isSyncing" x-text="currentStep === 4 ? 'К оплате' : 'Продолжить'"></span>
                            <div x-show="isSyncing" class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                        </button>
                    <?php else: ?>
                        <button wire:click="submitOrder" wire:loading.attr="disabled" :disabled="!conflictsConfirmed || isProcessing"
                            dusk="checkout-submit-button"
                            class="flex-1 px-6 py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-xl shadow-orange-200 hover:bg-orange-600 transition-all btn-press flex items-center justify-center gap-3 disabled:opacity-50">
                            <span wire:loading.remove>Подтвердить заказ</span>
                            <div wire:loading class="w-5 h-5 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\OSPanel\domains\RestoPWA\resources\views/livewire/order/checkout-wizard.blade.php ENDPATH**/ ?>