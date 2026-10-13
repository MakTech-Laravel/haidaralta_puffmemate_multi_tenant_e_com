import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { getEcho, getEchoGeneration } from '@/echo';
import type { Auth } from '@/types';

const ORDERS_CHANGED_EVENT = '.driver.orders-changed';

/**
 * Partial-reload the given props when staff assign an order to the signed-in
 * driver, so the list updates without a page refresh.
 *
 * Listens on the shared private user channel; cleanup only stops listening
 * because the global notification toast subscription uses the same channel.
 */
export function useDriverOrdersRealtime(only: string[]): void {
    const { auth } = usePage<{ auth: Auth }>().props;
    const userId = auth.user?.id;
    const onlyKey = only.join(',');
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

        const channel = echo.private(`App.Models.User.${userId}`);
        const onChanged = () => {
            router.reload({ only: onlyKey.split(',') });
        };

        channel.listen(ORDERS_CHANGED_EVENT, onChanged);

        return () => {
            try {
                channel.stopListening(ORDERS_CHANGED_EVENT, onChanged);
            } catch {
                // Echo may already be torn down during reconnect.
            }
        };
    }, [userId, onlyKey, echoGeneration]);
}
