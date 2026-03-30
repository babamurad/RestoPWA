<div x-data="{ isOffline: !navigator.onLine }"
     x-init="window.addEventListener('online', () => isOffline = false);
             window.addEventListener('offline', () => isOffline = true)"
     x-show="isOffline"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="offline-indicator border-bottom border-warning shadow-sm"
     style="display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: #fff3cd; color: #856404; padding: 10px 0; text-align: center;"
     id="offline-indicator">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Офлайн режим:</strong>
            <span class="ms-1">Подключение к сети потеряно. Некоторые функции могут быть недоступны.</span>
        </div>
    </div>
</div>
