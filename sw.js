/**
 * Veloce POS - Service Worker (PWA Mobile Shell Caching)
 * PT Taman Wisata Borobudur
 */

const CACHE_NAME = 'veloce-pos-v1';
const STATIC_ASSETS = [
    './',
    './index.php',
    './manifest.json',
    './assets/css/glassmorphism.css',
    './assets/js/app.js',
    './assets/js/pos.js',
    './assets/js/theme.js',
    './assets/images/logo_twb.png',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'
];

// Install Event - Pre-cache core app shell
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate Event - Clean up outdated caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - Network first for PHP/API, Cache first for static assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Bypass API, POST requests, and database calls
    if (event.request.method !== 'GET' || url.pathname.includes('api.php') || url.pathname.includes('controllers/')) {
        return;
    }

    // Static Assets: Stale-While-Revalidate
    if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.png') || url.pathname.endsWith('.jpg')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                const networkFetch = fetch(event.request).then(response => {
                    if (response && response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                }).catch(() => cached);
                return cached || networkFetch;
            })
        );
        return;
    }

    // Default: Network with cache fallback
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
