import { useEffect, useRef, useCallback } from 'react';

interface UseSessionHeartbeatOptions {
    enabled?: boolean;
    intervalMinutes?: number;
    onCsrfUpdate?: (newToken: string) => void;
    onSessionExpired?: () => void;
}

interface HeartbeatResponse {
    csrf_token: string;
    session_lifetime: number;
    timestamp: string;
}

function updateCsrfMetaTag(token: string): void {
    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
}

export function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function useSessionHeartbeat(options: UseSessionHeartbeatOptions = {}): void {
    const { enabled = true, intervalMinutes = 5, onCsrfUpdate, onSessionExpired } = options;

    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    const sendHeartbeat = useCallback(async () => {
        try {
            const response = await fetch('/session/heartbeat', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.status === 419) {
                onSessionExpired?.();
                window.location.reload();
                return;
            }

            if (response.status === 401) {
                window.location.href = '/login';
                return;
            }

            if (response.ok) {
                const data: HeartbeatResponse = await response.json();
                updateCsrfMetaTag(data.csrf_token);
                onCsrfUpdate?.(data.csrf_token);
            }
        } catch (error) {
            console.error('[SessionHeartbeat] Heartbeat failed:', error);
        }
    }, [onCsrfUpdate, onSessionExpired]);

    useEffect(() => {
        if (!enabled) return;

        const intervalMs = intervalMinutes * 60 * 1000;
        const initialTimeout = setTimeout(sendHeartbeat, 10_000);
        intervalRef.current = setInterval(sendHeartbeat, intervalMs);

        return () => {
            clearTimeout(initialTimeout);
            if (intervalRef.current) clearInterval(intervalRef.current);
        };
    }, [enabled, intervalMinutes, sendHeartbeat]);
}
