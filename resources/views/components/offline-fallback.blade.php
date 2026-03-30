<div x-data="{ isOffline: !navigator.onLine }"
     x-init="window.addEventListener('online', () => isOffline = false);
             window.addEventListener('offline', () => isOffline = true)"
     class="offline-fallback-wrapper">
    <div x-show="!isOffline">
        {{ $slot }}
    </div>
    
    <div x-show="isOffline" x-cloak class="offline-fallback p-5 text-center bg-light border rounded">
        <div class="mb-3 text-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                <path d="M23.64 7c-.45-.34-4.93-4-11.64-4-1.5 0-2.89.19-4.15.48L18.18 13.8 23.64 7zm-3.22 6.84l-2.76 2.74-3.77-3.77-3.77 3.77-2.74-2.76 3.77-3.77-3.77-3.76 2.74-2.74 3.77 3.77 3.77-3.77 2.74 2.74-3.77 3.77 3.77 3.76-2.74 2.76zM3.36 7l-.48.48C5.44 10.9 8.53 13.5 12 13.5c3.04 0 5.69-1.98 7.16-4.8l.4-.34.46.46 2.78 2.78C21.3 13.2 18.52 15.5 15 15.5c-3.47 0-6.55-2.6-8.64-6.5-.2-.38-.38-.78-.38-1.15 0-.55.45-1 1-1 .2 0 .39.06.55.16L12.48 12.2c.5.5 1.21.67 1.82.38l3.2-1.5-4.8-4.8-3.2 1.5c-.4-.1-.82-.02-1.14.3L2.27 13.27c-.28.46-.22 1.08.14 1.46l2.69 2.7c.46.46 1.08.59 1.71.3l3.2-1.5L4.1 20.9l-2.69-2.69c-.28-.46-.22-1.08.14-1.46l2.65-2.65L.77 17.36c-.31.15-.5.47-.5.81 0 .55.45 1 1 1 .34 0 .66-.19.81-.5l2.28-2.28z"/>
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
            </svg>
        </div>
        <h4 class="h5">Контент недоступен офлайн</h4>
        <p class="text-muted">Пожалуйста, проверьте подключение к сети, чтобы увидеть это содержимое.</p>
        <button onclick="window.location.reload()" class="btn btn-outline-primary btn-sm mt-2">
            Искать сеть
        </button>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
