const CACHE_NAME = 'chama-app-v1';
const OFFLINE_URL = '/offline';
const ASSETS_TO_CACHE = [
    '/',
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/favicon.ico',
    '/apple-touch-icon.png',
    '/manifest.json',
];

// Install Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                // Cache known assets
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
    self.skipWaiting();
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName))
            );
        })
    );
    self.clients.claim();
});

// Fetch Event Handler
self.addEventListener('fetch', (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Handle API requests
    if (event.request.url.includes('/api/')) {
        return networkFirst(event);
    }

    // Handle static assets
    if (event.request.url.match(/\.(css|js|woff2?|ttf|eot)$/)) {
        return cacheFirst(event);
    }

    // Handle images
    if (event.request.url.match(/\.(jpg|jpeg|png|gif|svg|webp)$/)) {
        return staleWhileRevalidate(event);
    }

    // Handle navigation requests
    if (event.request.mode === 'navigate') {
        return networkFirst(event);
    }

    // Default strategy
    return staleWhileRevalidate(event);
});

// Cache First Strategy
function cacheFirst(event) {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                return response || fetch(event.request).then((response) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, response.clone());
                        return response;
                    });
                });
            })
            .catch(() => {
                return caches.match('/offline');
            })
    );
}

// Network First Strategy
function networkFirst(event) {
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                return caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, response.clone());
                    return response;
                });
            })
            .catch(() => {
                return caches.match(event.request).then((response) => {
                    return response || caches.match('/offline');
                });
            })
    );
}

// Stale While Revalidate Strategy
function staleWhileRevalidate(event) {
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            const fetchPromise = fetch(event.request).then((networkResponse) => {
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, networkResponse.clone());
                });
                return networkResponse;
            });
            return cachedResponse || fetchPromise;
        })
    );
}

// Handle Push Notifications
self.addEventListener('push', (event) => {
    const options = {
        body: event.data.text(),
        icon: '/apple-touch-icon.png',
        badge: '/favicon.ico',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'view',
                title: 'View'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Chama App', options)
    );
});

// Handle Notification Click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});