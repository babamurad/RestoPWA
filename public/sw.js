const CACHE_NAME = 'resto-pwa-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                '/',
                '/build/assets/app.js',
            ]);
        })
    );
});

self.addEventListener('fetch', (event) => {
    // NetworkFirst strategy for API
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request).then(response => {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                    if (event.request.method === 'GET') {
                        cache.put(event.request, responseClone);
                    }
                });
                return response;
            }).catch(() => caches.match(event.request))
        );
        return;
    }

    // CacheFirst strategy for statics and other pages
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).then((fetchResponse) => {
                return caches.open(CACHE_NAME).then((cache) => {
                    if (event.request.method === 'GET') {
                        cache.put(event.request, fetchResponse.clone());
                    }
                    return fetchResponse;
                });
            });
        }).catch(() => {
            // Optional offline fallback
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
