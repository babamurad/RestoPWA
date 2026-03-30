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
