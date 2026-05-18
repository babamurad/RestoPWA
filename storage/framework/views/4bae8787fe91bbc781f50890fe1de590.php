<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">

    <title><?php echo e(config('app.name', 'RestoPWA')); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
        body {
            font-family: 'Inter', 'Outfit', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-900 overflow-x-hidden antialiased text-slate-100">
    <script>
        window.vapidPublicKey = '<?php echo e(config('services.push.public_key')); ?>';
        window.apiPingUrl = '<?php echo e(route('api.ping')); ?>';
    </script>

    <!-- Vue mount element -->
    <div id="app"></div>
</body>

</html>
<?php /**PATH C:\OSPanel\domains\RestoPWA\resources\views/app.blade.php ENDPATH**/ ?>