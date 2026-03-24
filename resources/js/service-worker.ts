/// <reference lib="webworker" />

declare const self: ServiceWorkerGlobalScope;

const CACHE_NAME = 'inte-school-v1';

const STATIC_ASSETS = [
    '/',
    '/manifest.json',
];

// --- Install: cache static assets ---
self.addEventListener('install', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// --- Activate: remove old caches ---
self.addEventListener('activate', (event: ExtendableEvent) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// --- Fetch: inject CSRF token on non-GET background sync requests ---
self.addEventListener('fetch', (event: FetchEvent) => {
    const { request } = event;

    // Only intercept same-origin requests
    if (!request.url.startsWith(self.location.origin)) {
        return;
    }

    // For non-GET requests, inject the XSRF token from cookie
    if (request.method !== 'GET') {
        event.respondWith(injectCsrfToken(request));
        return;
    }

    // For GET requests: network-first, fall back to cache
    event.respondWith(
        fetch(request).catch(() => caches.match(request).then(cached => cached ?? Response.error()))
    );
});

async function injectCsrfToken(request: Request): Promise<Response> {
    const token = getCookieValue('XSRF-TOKEN');

    if (!token) {
        return fetch(request);
    }

    const cloned = new Request(request, {
        headers: new Headers({
            ...Object.fromEntries(request.headers.entries()),
            'X-XSRF-TOKEN': decodeURIComponent(token),
        }),
    });

    return fetch(cloned);
}

function getCookieValue(name: string): string | null {
    const match = self.location.href.match(new RegExp(`(^|;)\\s*${name}\\s*=\\s*([^;]+)`));
    // Service worker doesn't have document.cookie — read from client if needed
    // For now, return null and rely on the client to pass tokens via headers
    return null;
}

// --- Push: display notification ---
self.addEventListener('push', (event: PushEvent) => {
    if (!event.data) return;

    let payload: { title?: string; body?: string; action_token?: string; icon?: string; tag?: string } = {};

    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Inte-School', body: event.data.text() };
    }

    const title = payload.title ?? 'Inte-School';
    const options: NotificationOptions = {
        body: payload.body ?? '',
        icon: payload.icon ?? '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: payload.tag ?? 'inte-school',
        data: {
            action_token: payload.action_token ?? null,
            url: '/dashboard',
        },
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// --- Notification click: open app and consume action token ---
self.addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close();

    const data = event.notification.data as { action_token?: string; url?: string };
    const targetUrl = data.url ?? '/dashboard';

    event.waitUntil(
        (async () => {
            // If there's an action token, consume it via a POST before navigating
            if (data.action_token) {
                try {
                    const token = getCsrfTokenFromClients();
                    await fetch('/action-token/consume', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': token ?? '',
                        },
                        body: JSON.stringify({ token: data.action_token }),
                    });
                } catch {
                    // Graceful fallback — proceed to navigation even if consume fails
                }
            }

            // Focus existing window or open a new one
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

function getCsrfTokenFromClients(): string | null {
    // Service workers cannot read cookies directly.
    // The token will be injected via the fetch event handler or client messaging.
    return null;
}
