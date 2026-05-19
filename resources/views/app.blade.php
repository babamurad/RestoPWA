<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">

    <title>{{ config('app.name', 'RestoPWA') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <x-pwa.meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Yandex Maps JS API v3 --}}
    @php
        $yandexJsKey = config('services.yandex_maps.js_key') ?: config('services.yandex_maps.key');
    @endphp
    @if($yandexJsKey)
    <script src="https://api-maps.yandex.ru/v3/?apikey={{ $yandexJsKey }}&lang=ru_RU" type="text/javascript"></script>
    @endif

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', 'Outfit', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-900 overflow-x-hidden antialiased text-slate-100">
    <script>
        window.vapidPublicKey = '{{ config('services.push.public_key') }}';
        window.apiPingUrl = '{{ route('api.ping') }}';
    </script>

    <!-- Vue mount element -->
    <div id="app"></div>
</body>

</html>
