<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">

    <title><?php echo e($title ?? config('app.name')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php if (isset($component)) { $__componentOriginal3ef942e40b8187d4f2e7c55cec4698e3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ef942e40b8187d4f2e7c55cec4698e3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pwa.meta','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pwa.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3ef942e40b8187d4f2e7c55cec4698e3)): ?>
<?php $attributes = $__attributesOriginal3ef942e40b8187d4f2e7c55cec4698e3; ?>
<?php unset($__attributesOriginal3ef942e40b8187d4f2e7c55cec4698e3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3ef942e40b8187d4f2e7c55cec4698e3)): ?>
<?php $component = $__componentOriginal3ef942e40b8187d4f2e7c55cec4698e3; ?>
<?php unset($__componentOriginal3ef942e40b8187d4f2e7c55cec4698e3); ?>
<?php endif; ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(config('services.yandex_maps.js_key')): ?>
    <script src="https://api-maps.yandex.ru/v3/?apikey=<?php echo e(config('services.yandex_maps.js_key')); ?>&lang=ru_RU" type="text/javascript"></script>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <style>
        [x-cloak] { display: none !important; }
        /* Yandex Maps v3 container fixes */
        .ymaps3x0--map, [class*="ymaps3"] { border-radius: 0; }
    </style>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body class="bg-gray-50 overflow-x-hidden" x-data="cartManager">
    <script>
        // Force unregister all service workers and clear caches
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
        if ('caches' in window) {
            caches.keys().then(function(names) {
                for (let name of names) caches.delete(name);
            });
        }
        
        window.vapidPublicKey = '<?php echo e(config('services.push.public_key')); ?>';
        window.apiPingUrl = '<?php echo e(route('api.ping')); ?>';
    </script>

    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-xl relative md:max-w-4xl lg:max-w-6xl xl:max-w-7xl lg:bg-transparent lg:shadow-none transition-all duration-300">
        <?php if (isset($component)) { $__componentOriginal30ed851a7370ef0c75347addc2809e2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal30ed851a7370ef0c75347addc2809e2c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.offline-indicator','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('offline-indicator'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal30ed851a7370ef0c75347addc2809e2c)): ?>
<?php $attributes = $__attributesOriginal30ed851a7370ef0c75347addc2809e2c; ?>
<?php unset($__attributesOriginal30ed851a7370ef0c75347addc2809e2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal30ed851a7370ef0c75347addc2809e2c)): ?>
<?php $component = $__componentOriginal30ed851a7370ef0c75347addc2809e2c; ?>
<?php unset($__componentOriginal30ed851a7370ef0c75347addc2809e2c); ?>
<?php endif; ?>

        <div class="flex flex-col min-h-screen">
            <div class="flex-1">
                <?php echo e($slot); ?>

            </div>
        </div>

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('cart.cart-drawer', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1689260005-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>

        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('geo.address-selector', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1689260005-1', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html><?php /**PATH C:\OSPanel\domains\RestoPWA\resources\views/components/layouts/app.blade.php ENDPATH**/ ?>