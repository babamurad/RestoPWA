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


async function checkConnectivity() {
    const wasOffline = !navigator.onLine || document.body.classList.contains('is-server-offline');
    
    if (!navigator.onLine) {
        setOfflineState(true, 'network');
        return;
    }

    try {
        const response = await fetch('/api/ping', { 
            method: 'HEAD', 
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache' } 
        });
        
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
