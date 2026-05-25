import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './vue/router';
import App from './vue/App.vue';
import './bootstrap';
import './services/CartService';
import './services/CartAlpine';
import Swal from 'sweetalert2'

window.Swal = Swal

let swRegistration = null;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered:', registration.scope);
                setupOrderSubmission(registration);
            })
            .catch((error) => {
                console.error('SW registration failed:', error);
            });
    });
}

setupOrderSubmission(null);
window.setupOrderSubmission = setupOrderSubmission;

function setupOrderSubmission(registration) {
    if (registration) {
        swRegistration = registration;
        return;
    }

    window.addEventListener('submit-order', async (e) => {
        const payload = e.detail;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch('/api/v1/orders', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Vendor-ID': payload.vendor_id,
                    ...(payload.idempotency_key ? { 'X-Idempotency-Key': payload.idempotency_key } : {}),
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const result = await response.json();

                if (result.data?.redirect_url) {
                    window.location.href = result.data.redirect_url;
                } else {
                    window.location.href = `/order/success/${result.data?.order_id || ''}`;
                }
            } else {
                const error = await response.json();
                window.dispatchEvent(new CustomEvent('submit-order-failed'));

                if (response.status === 401) {
                    window.Swal.fire({
                        title: 'Вы не авторизованы',
                        text: error.message || 'Для оформления заказа необходимо войти в профиль',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Войти',
                        cancelButtonText: 'Позже',
                        confirmButtonColor: '#f97316',
                        reverseButtons: true,
                        customClass: { popup: 'rounded-2xl' },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                        }
                    });
                } else {
                    window.Swal.fire({
                        title: 'Ошибка оформления заказа',
                        text: error.message || 'Попробуйте позже',
                        icon: 'error',
                        confirmButtonText: 'Закрыть',
                        confirmButtonColor: '#f97316',
                        customClass: { popup: 'rounded-2xl' },
                    });
                }
            }
        } catch (error) {
            console.error('Order submission error:', error);
            window.dispatchEvent(new CustomEvent('submit-order-failed'));

            if (window.CartService) {
                try {
                    await window.CartService.queueOrder(payload);
                    console.log('Order queued successfully in IndexedDB');
                } catch (dbError) {
                    console.error('Failed to queue order in IndexedDB:', dbError);
                }
            }

            if (swRegistration && swRegistration.active) {
                try {
                    await swRegistration.active.sync.register({
                        tag: 'order-sync',
                    });
                } catch (syncError) {
                    console.error('Background sync registration failed:', syncError);
                }
            }

            window.Swal.fire({
                title: 'Заказ сохранён!',
                text: 'Будет отправлен при появлении интернета',
                icon: 'success',
                timer: 4000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: { popup: 'rounded-2xl' },
            });
        }
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type === 'ORDER_SYNCED') {
                window.dispatchEvent(new CustomEvent('order-synced-from-offline', {
                    detail: event.data,
                }));
            }
        });
    }
}

async function checkConnectivity() {
    const wasOffline = !navigator.onLine || document.body.classList.contains('is-server-offline');

    if (!navigator.onLine) {
        setOfflineState(true, 'network');
        return;
    }

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5s timeout

        const response = await fetch(window.apiPingUrl || '/api/ping', {
            method: 'GET',
            cache: 'no-store',
            signal: controller.signal,
            headers: {
                'Cache-Control': 'no-cache',
                'Accept': 'application/json'
            }
        });

        clearTimeout(timeoutId);

        if (response.ok) {
            setOfflineState(false);
        } else {
            setOfflineState(true, 'server');
        }
    } catch (e) {
        setOfflineState(true, 'server');
    }
}

function setOfflineState(isOffline, type = 'network') {
    const indicator = document.getElementById('offline-indicator');
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

    // Dispatch custom event for Alpine components
    window.dispatchEvent(new CustomEvent('connectivity-changed', {
        detail: { isOffline, type }
    }));
}

// Check every 30 seconds
setInterval(checkConnectivity, 30000);

window.addEventListener('online', () => {
    checkConnectivity();
});

window.addEventListener('offline', () => {
    setOfflineState(true, 'network');
});

// Initial check
checkConnectivity();

const vapidPublicKey = window.vapidPublicKey || null;

async function askPushPermission() {
    if (!('Notification' in window) || !('serviceWorker' in navigator)) {
        return;
    }

    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        await subscribeToPush();
    }
}

async function subscribeToPush() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        await fetch('/api/v1/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(subscription)
        });

        console.log('Push subscription successful');
    } catch (error) {
        console.error('Push subscription failed:', error);
    }
}

async function unsubscribeFromPush() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await fetch('/api/v1/push/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ endpoint: subscription.endpoint })
            });

            await subscription.unsubscribe();
        }
    } catch (error) {
        console.error('Push unsubscription failed:', error);
    }
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

window.askPushPermission = askPushPermission;
window.subscribeToPush = subscribeToPush;
window.unsubscribeFromPush = unsubscribeFromPush;

// Mount Vue SPA conditionally
const appElement = document.getElementById('app');
if (appElement) {
    const app = createApp(App);
    const pinia = createPinia();
    app.use(pinia);
    app.use(router);
    app.mount('#app');
}
