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

     <?php $__env->slot('title', null, []); ?> Заказ оформлен - RestoPWA <?php $__env->endSlot(); ?>

    <div class="bg-slate-950 text-slate-100 min-h-screen flex flex-col items-center justify-center px-6 py-12 md:py-24 pb-24">
        <div class="max-w-lg w-full md:max-w-2xl text-center">
            
            
            <div class="relative mb-10 md:mb-12">
                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto bg-emerald-500/10 border border-emerald-500/20 rounded-full flex items-center justify-center animate-scale-in">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400 md:w-16 md:h-16">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="absolute inset-0 w-24 h-24 md:w-32 md:h-32 mx-auto bg-emerald-500/20 rounded-full animate-ping" style="animation-duration: 2s;"></div>
            </div>

            <h1 class="text-2xl md:text-4xl font-bold text-slate-100 mb-3 animate-slide-up">Заказ оформлен!</h1>
            <p class="text-slate-400 md:text-lg mb-10 md:mb-12 animate-slide-up" style="animation-delay: 0.1s">Ваш заказ успешно принят и скоро будет передан в работу</p>

            
            <div class="bg-slate-900/60 rounded-3xl p-8 md:p-10 border border-slate-800/40 backdrop-blur-xl shadow-2xl shadow-black/30 mb-10 md:mb-12 animate-slide-up" style="animation-delay: 0.2s">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse shadow-lg shadow-emerald-500/30"></div>
                    <span class="text-sm md:text-base font-bold text-emerald-400 uppercase tracking-widest">Заказ принят</span>
                </div>
                <div class="space-y-4 md:space-y-6">
                    <div class="flex justify-between items-center text-slate-400">
                        <span class="font-medium">Номер заказа</span>
                        <span class="font-bold text-slate-100 md:text-lg">#<?php echo e(substr($order->id, 0, 8)); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-400">
                        <span class="font-medium">Ожидаемое время</span>
                        <span class="font-bold text-slate-100 md:text-lg">30-45 мин</span>
                    </div>
                    <div class="flex justify-between items-center border-t border-slate-800/40 pt-5">
                        <span class="font-bold text-slate-500 uppercase tracking-widest text-xs">Итого к оплате</span>
                        <span class="text-2xl md:text-3xl font-black text-orange-500"><?php echo e(number_format($order->total, 0, '.', ' ')); ?> ₽</span>
                    </div>
                </div>
            </div>

            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-slide-up" style="animation-delay: 0.3s">
                <a href="<?php echo e($signedTrackingUrl ?? route('order.track', $order->id)); ?>" class="flex items-center justify-center gap-3 px-8 py-5 bg-orange-500 text-white font-bold rounded-2xl hover:bg-orange-600 shadow-xl shadow-orange-500/20 transition-all card-hover">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span>Отследить заказ</span>
                </a>
                <a href="<?php echo e(route('home')); ?>" class="flex items-center justify-center gap-3 px-8 py-5 bg-slate-900/80 text-slate-200 font-bold rounded-2xl border border-slate-800/50 hover:bg-slate-800/50 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-300"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>На главную</span>
                </a>
            </div>

        </div>
    </div>

    <style>
        body {
            background-color: #020617 !important; /* bg-slate-950 */
        }
        /* Target the parent container of the layout components to make them dark and seamless */
        .max-w-lg.mx-auto.bg-white {
            background-color: #020617 !important;
            box-shadow: none !important;
            border: none !important;
        }
        @keyframes scale-in {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-scale-in { animation: scale-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .animate-slide-up { opacity: 0; animation: slide-up 0.4s ease-out forwards; }
    </style>
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
<?php /**PATH C:\OSPanel\home\RestoPWA\resources\views/order/success.blade.php ENDPATH**/ ?>