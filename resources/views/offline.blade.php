<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">
    <title>Офлайн - RestoPWA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        orange: { 50: '#FFF0EB', 100: '#FFD9CC', 400: '#FF8A5C', 500: '#FF6B35', 600: '#E55A2B', 700: '#CC4A22' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; -webkit-tap-highlight-color: transparent; }
        .touch-feedback:active { transform: scale(0.95); transition: transform 0.1s; }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-xs w-full text-center">
        <!-- Illustration Area -->
        <div class="relative mb-10">
            <div class="w-32 h-32 mx-auto bg-orange-100 rounded-full flex items-center justify-center animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500">
                    <path d="M12 20h.01"/><path d="M2 10a10 10 0 0 1 1.74-5.5"/><path d="M4.6 7.6a6 6 0 0 1 2.2-2.2"/><path d="M12 10a4 4 0 0 1 0 8"/><path d="M16 10a10 10 0 0 1 1.74 14.5"/><path d="M22 10a10 10 0 0 0-1.74-5.5"/><path d="M19.4 7.6a6 6 0 0 0-2.2-2.2"/><path d="m2 2 20 20"/>
                </svg>
            </div>
            <div class="absolute -top-2 -right-2 bg-white p-2 rounded-full shadow-lg border border-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold text-gray-900 mb-3 tracking-tight">Нет сети</h1>
        <p class="text-gray-500 text-sm mb-10 leading-relaxed">
            Похоже, соединение с интернетом потеряно. <br>
            Эта страница еще не была сохранена для работы без сети.
        </p>

        <div class="space-y-3">
            <button onclick="window.location.reload()" class="w-full py-4 bg-orange-500 text-white font-bold rounded-2xl shadow-xl shadow-orange-500/30 hover:bg-orange-600 transition-all touch-feedback active:scale-95">
                Попробовать снова
            </button>
            <a href="/" class="block w-full py-4 bg-white text-gray-700 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 transition-all touch-feedback active:scale-95">
                На главную
            </a>
        </div>

        <!-- Connection Badge -->
        <div class="mt-12 flex items-center justify-center gap-2">
            <div class="w-1.5 h-1.5 bg-red-400 rounded-full animate-pulse"></div>
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Офлайн режим</span>
        </div>
    </div>

    <script>
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>
