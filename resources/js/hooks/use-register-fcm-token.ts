import { useEffect, useRef } from 'react';
import { requestFcmToken } from '@/lib/fcm';

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * After auth: if notification permission is already granted, silently refresh
 * and register the FCM token for the current user. Does not prompt.
 */
export function useRegisterFcmToken(authUserId?: number): void {
    const lastUserId = useRef<number | undefined>(undefined);

    useEffect(() => {
        if (!authUserId) {
            return;
        }

        if (typeof Notification === 'undefined') {
            return;
        }

        if (Notification.permission !== 'granted') {
            return;
        }

        if (lastUserId.current === authUserId) {
            return;
        }

        lastUserId.current = authUserId;
        let cancelled = false;

        void (async () => {
            try {
                const token = await requestFcmToken();

                if (!token || cancelled) {
                    return;
                }

                await fetch('/fcm/register', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        token,
                        platform: 'web',
                        device_name: navigator.userAgent.slice(0, 255),
                    }),
                });
            } catch {
                // Silent: registration is best-effort on shell mount.
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [authUserId]);
}
