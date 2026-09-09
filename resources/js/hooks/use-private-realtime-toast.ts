import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { getEcho, getEchoGeneration } from '@/echo';
import type { RealtimeTestPayload } from '@/realtime/types';
import type { Auth } from '@/types';

type InertiaPageLike = {
    props?: {
        auth?: Auth;
    };
};

/**
 * Show a Sonner toast for a private realtime.test payload.
 * Uses a stable toast id so Sonner itself replaces duplicates.
 */
export function showRealtimeTestToast(payload: RealtimeTestPayload): void {
    const message =
        payload.message?.trim() || 'Realtime notification received.';
    const id =
        payload.triggered_at?.trim() ||
        `realtime-test-${payload.user_id ?? 'anon'}-${message}`;

    toast.success(message, {
        id,
        description: 'Private realtime channel',
        duration: 6000,
    });
}

function readAuthUserId(page?: InertiaPageLike | null): number | undefined {
    const id = page?.props?.auth?.user?.id;

    return typeof id === 'number' ? id : undefined;
}

function pageFromInertiaEvent(event: unknown): InertiaPageLike | undefined {
    if (
        typeof event === 'object' &&
        event !== null &&
        'detail' in event &&
        typeof event.detail === 'object' &&
        event.detail !== null &&
        'page' in event.detail
    ) {
        return event.detail.page as InertiaPageLike;
    }

    return undefined;
}

/**
 * Keep a private Echo subscription for the authenticated user and surface
 * `realtime.test` payloads as Sonner toasts. Does not change Reverb config.
 *
 * Inertia v3 has no `router.page`. Seed from `withApp`'s page / AppToaster
 * and sync via navigate/success `detail.page` (not `usePage`).
 */
export function usePrivateRealtimeToast(initialUserId?: number): void {
    const [userId, setUserId] = useState<number | undefined>(initialUserId);
    const [echoGeneration, setEchoGeneration] = useState(() =>
        getEchoGeneration(),
    );

    useEffect(() => {
        setUserId(initialUserId);
    }, [initialUserId]);

    useEffect(() => {
        const syncUserId = (event: unknown) => {
            const page = pageFromInertiaEvent(event);

            // Ignore malformed events so we do not wipe a valid seed user id.
            if (!page) {
                return;
            }

            setUserId(readAuthUserId(page));
        };

        const offNavigate = router.on('navigate', syncUserId);
        const offSuccess = router.on('success', syncUserId);

        return () => {
            offNavigate();
            offSuccess();
        };
    }, []);

    useEffect(() => {
        const onReconnected = () => setEchoGeneration(getEchoGeneration());

        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, []);

    useEffect(() => {
        if (!userId) {
            return;
        }

        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channelName = `App.Models.User.${userId}`;
        const channel = echo.private(channelName);

        const onPrivateTest = (payload: RealtimeTestPayload) => {
            showRealtimeTestToast(payload);
        };

        channel.listen('.realtime.test', onPrivateTest);

        return () => {
            try {
                channel.stopListening('.realtime.test', onPrivateTest);
            } catch {
                // Echo may already be torn down during reconnect.
            }
        };
    }, [userId, echoGeneration]);
}
