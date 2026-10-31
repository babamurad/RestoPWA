import apiClient from './api/client';

// apiClient.baseURL is '/api/v1' on web or '<mobile-api-base>/api/v1' inside Capacitor;
// ping lives at '.../api/ping' in both cases.
const pingUrl = apiClient.defaults.baseURL.replace(/\/v1$/, '') + '/ping';

export function setOfflineState(isOffline, type = 'network') {
    if (isOffline) {
        document.body.classList.add('is-offline');
        if (type === 'server') {
            document.body.classList.add('is-server-offline');
        }
        if (window.Livewire) window.Livewire.dispatch('browser-offline');
    } else {
        document.body.classList.remove('is-offline', 'is-server-offline');
        if (window.Livewire) window.Livewire.dispatch('browser-online');
    }

    window.dispatchEvent(new CustomEvent('connectivity-changed', {
        detail: { isOffline, type },
    }));
}

export async function checkConnectivity() {
    if (!navigator.onLine) {
        setOfflineState(true, 'network');
        return;
    }

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        const response = await fetch(pingUrl, {
            method: 'GET',
            cache: 'no-store',
            signal: controller.signal,
            headers: {
                'Cache-Control': 'no-cache',
                'Accept': 'application/json',
            },
        });

        clearTimeout(timeoutId);
        setOfflineState(!response.ok, 'server');
    } catch (e) {
        setOfflineState(true, 'server');
    }
}

export function startConnectivityMonitor() {
    setInterval(checkConnectivity, 30000);
    window.addEventListener('online', checkConnectivity);
    window.addEventListener('offline', () => setOfflineState(true, 'network'));
    checkConnectivity();
}
