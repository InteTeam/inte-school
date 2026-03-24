/* Inte-School Service Worker
 * Source of truth: resources/js/service-worker.ts
 * This file is served directly from the origin root (/sw.js) and must NOT be hashed.
 */

const CACHE_NAME = 'inte-school-v1';
const STATIC_ASSETS = ['/', '/manifest.json'];

// --- Install ---
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// --- Activate ---
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// --- Fetch: network-first for GET; inject XSRF token for mutations ---
self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (!request.url.startsWith(self.location.origin)) return;

    if (request.method !== 'GET') {
        event.respondWith(fetchWithCsrf(request));
        return;
    }

    event.respondWith(
        fetch(request).catch(() => caches.match(request).then(cached => cached || Response.error()))
    );
});

async function fetchWithCsrf(request) {
    // Read XSRF-TOKEN from cookie (available in SW on same origin)
    const token = getCookie('XSRF-TOKEN');
    if (!token) return fetch(request);

    const headers = new Headers(request.headers);
    headers.set('X-XSRF-TOKEN', decodeURIComponent(token));

    return fetch(new Request(request, { headers }));
}

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|;)\\s*' + name + '\\s*=\\s*([^;]+)'));
    return match ? match[1] : null;
}

// --- Push: display notification ---
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload = {};
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Inte-School', body: event.data.text() };
    }

    const title = payload.title || 'Inte-School';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: payload.tag || 'inte-school',
        data: {
            action_token: payload.action_token || null,
            url: payload.url || '/dashboard',
        },
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// --- Notification click: consume action token then navigate ---
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification.data || {};
    const targetUrl = data.url || '/dashboard';

    event.waitUntil(
        (async () => {
            if (data.action_token) {
                try {
                    const token = getCookie('XSRF-TOKEN');
                    await fetch('/action-token/consume', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': token ? decodeURIComponent(token) : '',
                        },
                        body: JSON.stringify({ token: data.action_token }),
                    });
                } catch {
                    // Graceful fallback — navigate anyway
                }
            }

            const allClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            for (const client of allClients) {
                if ('focus' in client) {
                    await client.focus();
                    client.postMessage({ type: 'NAVIGATE', url: targetUrl });
                    return;
                }
            }

            await self.clients.openWindow(targetUrl);
        })()
    );
});
