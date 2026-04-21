<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <x-pwa.meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-gray-50 overflow-x-hidden" x-data="cartManager">
    <script>
        window.vapidPublicKey = '{{ config('services.push.public_key') }}';
        window.apiPingUrl = '{{ route('api.ping') }}';
    </script>

    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-xl relative md:max-w-4xl lg:max-w-6xl xl:max-w-7xl lg:bg-transparent lg:shadow-none transition-all duration-300">
        <x-offline-indicator />

        <div class="flex flex-col min-h-screen">
            <div class="flex-1">
                {{ $slot }}
            </div>
        </div>

        <livewire:cart.cart-drawer />
    </div>

    @livewireScripts
</body>

</html>