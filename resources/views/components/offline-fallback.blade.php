<div x-data="{ isOffline: !navigator.onLine }"
     x-init="window.addEventListener('online', () => isOffline = false);
             window.addEventListener('offline', () => isOffline = true)"
     class="offline-fallback-wrapper">
    <div x-show="!isOffline">
        {{ $slot }}
    </div>
    
    <div x-show="isOffline" x-cloak class="offline-fallback p-5 text-center bg-light border rounded">
        <div class="mb-3 text-secondary">
            <i class="fas fa-wifi-slash fa-3x"></i>
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
