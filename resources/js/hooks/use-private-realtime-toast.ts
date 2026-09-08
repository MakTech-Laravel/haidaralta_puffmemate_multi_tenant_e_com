import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { getEcho, getEchoGeneration } from '@/echo';
import type { RealtimeTestPayload } from '@/realtime/types';
import type { Auth } from '@/types';

/**
 * Keep a private Echo subscription for the authenticated user and surface
 * `realtime.test` payloads as Sonner toasts. Does not change Reverb config.
 */
export function usePrivateRealtimeToast(): void {
    const userId = usePage<{ auth: Auth }>().props.auth.user?.id;
    const [echoGeneration, setEchoGeneration] = useState(() =>
        getEchoGeneration(),
    );

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
            const message = payload.message?.trim();

            toast.info(message && message.length > 0 ? message : 'Realtime notification received.');
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
