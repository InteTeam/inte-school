import { useState, useEffect, useCallback } from 'react';

interface UsePushNotificationsReturn {
    isSupported: boolean;
    permission: NotificationPermission;
    isSubscribed: boolean;
    isLoading: boolean;
    error: string | null;
    subscribe: () => Promise<void>;
    unsubscribe: () => Promise<void>;
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function arrayBufferToBase64(buffer: ArrayBuffer | null): string {
    if (!buffer) return '';
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function getCsrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

export function usePushNotifications(vapidPublicKey: string | null): UsePushNotificationsReturn {
    const [isSupported, setIsSupported] = useState(false);
    const [permission, setPermission] = useState<NotificationPermission>('default');
    const [isSubscribed, setIsSubscribed] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [registration, setRegistration] = useState<ServiceWorkerRegistration | null>(null);

    useEffect(() => {
        const checkSupport = async () => {
            const supported =
                'serviceWorker' in navigator &&
                'PushManager' in window &&
                'Notification' in window;
            setIsSupported(supported);

            if (!supported) {
                setIsLoading(false);
                return;
            }

            setPermission(Notification.permission);

            try {
                const reg = await navigator.serviceWorker.ready;
                setRegistration(reg);
                const subscription = await reg.pushManager.getSubscription();
                setIsSubscribed(!!subscription);
            } catch (err) {
                console.error('Error checking push subscription:', err);
            }

            setIsLoading(false);
        };

        checkSupport();
    }, []);

    const subscribe = useCallback(async () => {
        if (!registration || !vapidPublicKey) {
            setError('Push notifications are not configured.');
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            if (Notification.permission === 'default') {
                const result = await Notification.requestPermission();
                setPermission(result);
                if (result !== 'granted') {
                    setError('Notification permission was denied.');
                    setIsLoading(false);
                    return;
                }
            } else if (Notification.permission === 'denied') {
                setError('Notifications are blocked. Please enable them in browser settings.');
                setIsLoading(false);
                return;
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            const response = await fetch('/notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
                        auth: arrayBufferToBase64(subscription.getKey('auth')),
                    },
                }),
            });

            if (!response.ok) throw new Error('Failed to save subscription to server.');

            setIsSubscribed(true);
        } catch (err) {
            console.error('Error subscribing to push:', err);
            setError(err instanceof Error ? err.message : 'Failed to enable notifications.');
        }

        setIsLoading(false);
    }, [registration, vapidPublicKey]);

    const unsubscribe = useCallback(async () => {
        if (!registration) return;

        setIsLoading(true);
        setError(null);

        try {
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();

                await fetch('/notifications/unsubscribe', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });
            }

            setIsSubscribed(false);
        } catch (err) {
            console.error('Error unsubscribing from push:', err);
            setError(err instanceof Error ? err.message : 'Failed to disable notifications.');
        }

        setIsLoading(false);
    }, [registration]);

    return { isSupported, permission, isSubscribed, isLoading, error, subscribe, unsubscribe };
}
