<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['restaurant']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['restaurant']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<a href="<?php echo e(route('restaurants.show', $restaurant->slug)); ?>" class="flex lg:block gap-4 p-3 bg-white rounded-2xl shadow-sm border border-gray-100 cursor-pointer card-hover touch-feedback">
    <div class="relative flex-shrink-0 w-24 h-24 lg:w-full lg:h-40 overflow-hidden rounded-xl">
        <img src="<?php echo e($restaurant->image_url); ?>" alt="<?php echo e($restaurant->name); ?>" class="w-full h-full object-cover" loading="lazy">
    </div>
    <div class="flex flex-col justify-center flex-1 min-w-0 lg:justify-start">
        <h3 class="font-semibold text-gray-900 truncate"><?php echo e($restaurant->name); ?></h3>
        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
            <div class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#FBBF24" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span class="font-medium text-gray-700"><?php echo e($restaurant->rating); ?></span>
            </div>
            <div class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span><?php echo e($restaurant->delivery_time); ?> мин</span>
            </div>
        </div>
        <div class="flex items-center gap-1 mt-1 text-sm text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="truncate"><?php echo e($restaurant->categories->pluck('name')->join(' • ')); ?></span>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($restaurant->delivery_fee == 0): ?>
            <span class="mt-2 text-xs font-medium text-green-600">Бесплатная доставка</span>
        <?php else: ?>
            <span class="mt-2 text-xs text-gray-500">Доставка <?php echo e($restaurant->delivery_fee); ?> ₽</span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</a><?php /**PATH C:\OSPanel\domains\RestoPWA\resources\views/components/restaurant-card.blade.php ENDPATH**/ ?>