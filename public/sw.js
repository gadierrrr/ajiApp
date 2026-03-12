/**
 * AJI App Service Worker
 * Handles offline caching and background sync
 */

const CACHE_VERSION = 'v1.1.2';
const CACHE_NAME = `aji-app-${CACHE_VERSION}`;
const DATA_CACHE_NAME = `aji-app-data-${CACHE_VERSION}`;

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/offline.php',
    '/manifest.json',
    '/assets/css/styles.css',
    '/assets/css/tailwind.min.css',
    '/assets/js/app.min.js',
    '/assets/js/map.js',
    '/assets/js/filters.js',
    '/assets/js/geolocation.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png'
];

// External resources to cache
const EXTERNAL_ASSETS = [
    'https://cdn.tailwindcss.com',
    'https://unpkg.com/htmx.org@1.9.10',
    'https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css',
    'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css'
];

// Install event - cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                // Cache local assets
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                // Skip waiting to activate immediately
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Precache failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    const currentCaches = [CACHE_NAME, DATA_CACHE_NAME];

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name.startsWith('aji-app-') && !currentCaches.includes(name))
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => {
                // Take control of all pages immediately
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // API requests - Network first with data cache fallback
    if (url.pathname.startsWith('/api/')) {
        // For beach list/detail APIs, cache the response
        if (url.pathname.includes('beaches') || url.pathname.includes('beach-detail')) {
            event.respondWith(
                fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(DATA_CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Try data cache for offline access
                        return caches.match(event.request)
                            .then((cached) => {
                                if (cached) return cached;
                                return new Response(
                                    JSON.stringify({ success: false, error: 'Offline - data not cached' }),
                                    { headers: { 'Content-Type': 'application/json' } }
                                );
                            });
                    })
            );
            return;
        }

        // Other API requests - network only
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return new Response(
                        JSON.stringify({ success: false, error: 'Offline' }),
                        { headers: { 'Content-Type': 'application/json' } }
                    );
                })
        );
        return;
    }

    // Skip auth endpoints
    if (url.pathname.startsWith('/auth/') ||
        url.pathname.includes('login') ||
        url.pathname.includes('logout')) {
        return;
    }

    // For HTML pages - Network first, fallback to cache, then offline page
    if (event.request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Clone and cache successful responses
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Try cache first
                    return caches.match(event.request)
                        .then((cached) => {
                            if (cached) return cached;
                            // Fallback to offline page
                            return caches.match('/offline.php');
                        });
                })
        );
        return;
    }

    // For images - Cache first with network fallback
    if (event.request.destination === 'image') {
        event.respondWith(
            caches.match(event.request)
                .then((cached) => {
                    if (cached) return cached;

                    return fetch(event.request)
                        .then((response) => {
                            if (response.ok) {
                                const clone = response.clone();
                                caches.open(CACHE_NAME).then((cache) => {
                                    cache.put(event.request, clone);
                                });
                            }
                            return response;
                        })
                        .catch(() => {
                            // Return placeholder for failed images
                            return new Response(
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="#e5e7eb" width="100" height="100"/><text x="50" y="55" text-anchor="middle" fill="#9ca3af" font-size="40">🏖️</text></svg>',
                                { headers: { 'Content-Type': 'image/svg+xml' } }
                            );
                        });
                })
        );
        return;
    }

    // For other assets (CSS, JS) - Stale while revalidate
    event.respondWith(
        caches.match(event.request)
            .then((cached) => {
                const fetchPromise = fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || fetchPromise;
            })
    );
});

// Handle messages from the app
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data === 'clearCache') {
        caches.delete(CACHE_NAME);
    }
});

// Background sync for offline actions (favorites, reviews)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-favorites') {
        event.waitUntil(syncFavorites());
    }

    if (event.tag === 'sync-reviews') {
        event.waitUntil(syncReviews());
    }
});

// Sync pending favorites
async function syncFavorites() {
    // This would sync any offline favorite actions
    // Implementation depends on IndexedDB storage of pending actions
}

// Sync pending reviews
async function syncReviews() {
}

// Push notifications (for future use)
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();

    event.waitUntil(
        self.registration.showNotification(data.title || 'AJI', {
            body: data.body || '',
            icon: '/assets/icons/icon-192x192.png',
            badge: '/assets/icons/icon-96x96.png',
            data: data.url || '/'
        })
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data || '/')
    );
});
