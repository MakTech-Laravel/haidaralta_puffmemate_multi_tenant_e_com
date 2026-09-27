import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { getEcho, getEchoGeneration } from '@/echo';
import type { Auth } from '@/types';

type InertiaPageLike = {
    props?: {
        auth?: Auth;
    };
};

type NotificationCreatedPayload = {
    id?: number;
    type?: string;
    title?: string;
    message?: string;
};

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

export function useInAppNotification(initialUserId?: number): void {
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

        const onCreated = (payload: NotificationCreatedPayload) => {
            const title = payload.title?.trim() || 'New notification';

            toast.info(title, {
                id: `inbox-${payload.id ?? title}`,
                description: payload.message?.trim() || undefined,
                duration: 6000,
            });

            router.reload({
                only: ['notifications', 'customerNotifications'],
            });
        };

        channel.listen('.notification.created', onCreated);

        return () => {
            try {
                channel.stopListening('.notification.created', onCreated);
            } catch {
                // Echo may already be torn down during reconnect.
            }
        };
    }, [userId, echoGeneration]);
}
