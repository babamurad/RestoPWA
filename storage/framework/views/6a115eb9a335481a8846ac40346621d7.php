<?php
    $formattedPrice = number_format($totalPrice, 0, '.', ' ');
    $canCheckout = !empty($items); // Remove !$isOffline to allow offline checkout queueing as per plan
?>

<div x-data="{ isOpen: <?php if ((object) ('isOpen') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOpen'->value()); ?>')<?php echo e('isOpen'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOpen'); ?>')<?php endif; ?> }" 
     @open-cart.window="isOpen = true"
     @close-cart.window="isOpen = false"
     x-on:keydown.escape.window="isOpen = false"
     class="relative z-50">
    
    <!-- Backdrop -->
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm"
         @click="isOpen = false">
    </div>

    <!-- Drawer -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-0 h-full w-full max-w-sm bg-gray-50 shadow-2xl flex flex-col">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-4 h-14 bg-white border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Корзина</h2>
            <button @click="isOpen = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors touch-feedback">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Offline Indicator -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isOffline): ?>
            <div class="bg-orange-50 border-b border-orange-100 px-4 py-2 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><path d="m17 2 3 3-3 3"/><path d="m7 22-3-3 3-3"/><path d="M20 5H9a4 4 0 0 0-4 4v7"/><path d="M4 19h11a4 4 0 0 0 4-4V9"/></svg>
                <span class="text-xs text-orange-700 font-medium font-inter">Офлайн режим. Заказ будет отправлен при подключении.</span>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($items)): ?>
                <div class="flex flex-col items-center justify-center h-full text-center px-6">
                    <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Корзина пуста</h3>
                    <p class="text-sm text-gray-500 mt-2">Добавьте блюда, чтобы оформить заказ</p>
                </div>
            <?php else: ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex gap-3 p-3 bg-white rounded-2xl border border-gray-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['image'])): ?>
                            <div class="flex-shrink-0 w-16 h-16 overflow-hidden rounded-xl border border-gray-50">
                                <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['productName']); ?>" class="w-full h-full object-cover">
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <div class="flex flex-col flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900 line-clamp-1 text-sm"><?php echo e($item['productName'] ?? 'Товар'); ?></h4>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['vendorName'])): ?>
                                        <span class="text-[10px] text-gray-400 uppercase tracking-tight"><?php echo e($item['vendorName']); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <button wire:click="removeItem(<?php echo e($item['id'] ?? 0); ?>)" class="p-1 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="font-bold text-gray-900"><?php echo e(number_format($item['price'] * ($item['quantity'] ?? 1), 0, '.', ' ')); ?> ₽</span>
                                <div class="flex items-center gap-2 bg-gray-50 p-1 rounded-full border border-gray-100">
                                    <button wire:click="updateQuantity(<?php echo e($item['id'] ?? 0); ?>, <?php echo e(($item['quantity'] ?? 1) - 1); ?>)" 
                                            class="flex items-center justify-center w-6 h-6 rounded-full bg-white text-gray-700 shadow-sm hover:bg-gray-100 transition-colors touch-feedback">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                                    </button>
                                    <span class="w-5 text-center font-bold text-xs"><?php echo e($item['quantity'] ?? 1); ?></span>
                                    <button wire:click="updateQuantity(<?php echo e($item['id'] ?? 0); ?>, <?php echo e(($item['quantity'] ?? 1) + 1); ?>)" 
                                            class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-500 text-white shadow-sm hover:bg-orange-600 transition-colors touch-feedback">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- Footer -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($items)): ?>
            <div class="bg-white border-t border-gray-100 p-4 space-y-3 pb-8">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Сумма заказа</span>
                    <span class="text-lg font-bold text-gray-900"><?php echo e($formattedPrice); ?> ₽</span>
                </div>
                <button wire:click="checkout" dusk="cart-checkout-button" class="w-full flex items-center justify-center gap-3 h-14 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/30 transition-all touch-feedback active:scale-95 group">
                    <span>К оформлению</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
                <button wire:click="clearCart" class="w-full py-2 text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    Очистить корзину
                </button>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH C:\OSPanel\home\RestoPWA\resources\views/livewire/cart/cart-drawer.blade.php ENDPATH**/ ?>