const CACHE_NAME = 'resto-pwa-v1';
const STATIC_CACHE = 'resto-static-v1';
const API_CACHE = 'resto-api-v1';

const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/offline',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE).then((cache) => {
                return cache.addAll(STATIC_ASSETS);
            }),
            caches.open(API_CACHE),
        ])
    );
    self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const responseClone = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match('/offline');
                    });
                })
        );
        return;
    }

    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request, API_CACHE));
        return;
    }

    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.match(/\.(js|css|woff2?|ttf|otf|eot)$/)
    ) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    if (request.destination === 'image') {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    event.respondWith(cacheFirst(request, STATIC_CACHE));
});

async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        const responseClone = response.clone();
        
        if (request.method === 'GET' && response.ok) {
            const cache = await caches.open(cacheName);
            await cache.put(request, responseClone);
        }
        
        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response(
            JSON.stringify({ error: 'Offline', message: 'Нет подключения к интернету' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

async function cacheFirst(request, cacheName) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        
        if (response.ok && request.method === 'GET') {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        return new Response('Network error', { status: 503 });
    }
}

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => {
                        return name.startsWith('resto-') &&
                               name !== STATIC_CACHE &&
                               name !== API_CACHE &&
                               name !== CACHE_NAME;
                    })
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('sync', (event) => {
    if (event.tag === 'order-sync') {
        event.waitUntil(syncOrders());
    }
    if (event.tag === 'menu-sync') {
        event.waitUntil(syncMenu());
    }
});

const DB_NAME = 'RestoCart';
const DB_VERSION = 1;

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => reject(request.error);
        
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('pendingOrders')) {
                const store = db.createObjectStore('pendingOrders', { keyPath: 'id' });
                store.createIndex('createdAt', 'createdAt', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('menuCache')) {
                db.createObjectStore('menuCache', { keyPath: 'vendorId' });
            }
        };
    });
}

async function syncOrders() {
    try {
        const db = await openDB();
        const tx = db.transaction('pendingOrders', 'readonly');
        const store = tx.objectStore('pendingOrders');
        const request = store.getAll();
        
        const orders = await new Promise((resolve, reject) => {
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
        
        if (orders.length === 0) {
            return;
        }
        
        for (const order of orders) {
            try {
                const response = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(order),
                });
                
                if (response.ok) {
                    await deletePendingOrder(order.id);
                    await showNotification('Заказ успешно отправлен', {
                        body: 'Ваш заказ принят и готовится',
                        icon: '/icons/icon-192.png',
                    });
                } else {
                    await incrementRetry(order.id);
                }
            } catch (error) {
                await incrementRetry(order.id);
            }
        }
    } catch (error) {
        console.error('Sync orders error:', error);
    }
}

async function deletePendingOrder(orderId) {
    const db = await openDB();
    const tx = db.transaction('pendingOrders', 'readwrite');
    const store = tx.objectStore('pendingOrders');
    store.delete(orderId);
    
    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function incrementRetry(orderId) {
    const db = await openDB();
    const tx = db.transaction('pendingOrders', 'readwrite');
    const store = tx.objectStore('pendingOrders');
    
    const getRequest = store.get(orderId);
    
    getRequest.onsuccess = () => {
        const order = getRequest.result;
        if (order) {
            order.retries = (order.retries || 0) + 1;
            
            if (order.retries > 3) {
                order.status = 'failed';
                showNotification('Ошибка отправки заказа', {
                    body: 'Не удалось отправить заказ. Попробуйте позже вручную',
                    icon: '/icons/icon-192.png',
                });
            }
            
            store.put(order);
        }
    };
}

async function showNotification(title, options) {
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        self.registration.showNotification(title, options);
    }
}

async function syncMenu() {
    try {
        const db = await openDB();
        const tx = db.transaction('menuCache', 'readonly');
        const store = tx.objectStore('menuCache');
        const request = store.getAll();
        
        const cachedMenus = await new Promise((resolve, reject) => {
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
        
        for (const menu of cachedMenus) {
            try {
                const response = await fetch(`/api/v1/menu/${menu.vendorSlug}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    const updateTx = db.transaction('menuCache', 'readwrite');
                    const updateStore = updateTx.objectStore('menuCache');
                    updateStore.put({
                        vendorId: menu.vendorId,
                        vendorSlug: menu.vendorSlug,
                        data: data,
                        updatedAt: new Date().toISOString(),
                    });
                }
            } catch (error) {
                console.error('Menu sync error for vendor:', menu.vendorId, error);
            }
        }
    } catch (error) {
        console.error('Menu sync error:', error);
    }
}

async function registerPeriodicSync() {
    if ('periodicSync' in self.registration) {
        try {
            const permission = await navigator.permissions.query({
                name: 'periodic-background-sync',
            });
            
            if (permission.state === 'granted') {
                await self.registration.periodicSync.register('menu-sync', {
                    minInterval: 60 * 60 * 1000,
                });
                console.log('Periodic background sync registered');
            }
        } catch (error) {
            console.log('Periodic background sync not supported:', error);
        }
    }
}

if ('periodicSync' in self.registration) {
    self.addEventListener('periodicsync', (event) => {
        if (event.tag === 'menu-sync') {
            event.waitUntil(syncMenu());
        }
    });
}

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'REGISTER_PERIODIC_SYNC') {
        registerPeriodicSync();
    }
});

self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    
    const options = {
        body: data.body || '',
        icon: data.icon || '/icon-192x192.png',
        badge: data.badge || '/icon-192x192.png',
        data: data.data || {},
        vibrate: [100, 50, 100],
        actions: [
            { action: 'open', title: 'Открыть' },
            { action: 'close', title: 'Закрыть' }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Уведомление', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        const orderId = event.notification.data?.order_id;
        
        if (orderId) {
            event.waitUntil(
                clients.openWindow(`/order/${orderId}/track`)
            );
        } else {
            event.waitUntil(
                clients.openWindow('/')
            );
        }
    }
});
