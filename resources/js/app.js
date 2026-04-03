import './bootstrap';
import './services/CartService';
import './services/CartAlpine';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered:', registration.scope);
            })
            .catch((error) => {
                console.error('SW registration failed:', error);
            });
    });
}

window.addEventListener('online', () => {
    document.body.classList.remove('is-offline');
    if (window.Livewire) {
        window.Livewire.dispatch('browser-online');
    }
});

window.addEventListener('offline', () => {
    document.body.classList.add('is-offline');
    if (window.Livewire) {
        window.Livewire.dispatch('browser-offline');
    }
});

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
