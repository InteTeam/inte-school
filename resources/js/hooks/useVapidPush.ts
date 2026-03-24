import { useEffect, useState } from 'react';
import axios from 'axios';

type PushState = 'idle' | 'requesting' | 'subscribed' | 'denied' | 'unsupported';

export function useVapidPush(vapidPublicKey: string | null) {
    const [state, setState] = useState<PushState>('idle');

    useEffect(() => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            setState('unsupported');
            return;
        }

        if (Notification.permission === 'denied') {
            setState('denied');
        }
    }, []);

    async function subscribe(): Promise<void> {
        if (!vapidPublicKey) return;
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        setState('requesting');

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                setState('denied');
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            await axios.post('/device-registration', {
                push_subscription: subscription.toJSON(),
            });

            setState('subscribed');
        } catch {
            setState('idle');
        }
    }

    return { state, subscribe };
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);

    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}
