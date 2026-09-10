import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { getEcho, getEchoGeneration } from '@/echo';
import type { DatabaseBackupHistoryUpdatedPayload } from '@/realtime/types';

const CHANNEL = 'platform.database-backups';
const EVENT = '.database-backup.history-updated';

function reloadBackupProps(): void {
    router.reload({
        only: ['backups', 'histories'],
        preserveScroll: true,
    });
}

function hardReloadPage(): void {
    window.location.assign(window.location.href);
}

/**
 * Live-refresh platform backup archives + operator history via Reverb.
 * Leaves the channel on unmount (page-scoped; unlike the user toast channel).
 *
 * Restore jobs DROP SCHEMA public mid-run — never Inertia-reload while a restore
 * is pending/running, and hard-reload the browser when restore finishes.
 */
export function useDatabaseBackupRealtime(): void {
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
        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channel = echo.private(CHANNEL);

        const onUpdated = (payload: DatabaseBackupHistoryUpdatedPayload) => {
            if (payload.type === 'restore') {
                if (
                    payload.status === 'completed' ||
                    payload.status === 'failed'
                ) {
                    hardReloadPage();

                    return;
                }

                return;
            }

            reloadBackupProps();
        };

        channel.listen(EVENT, onUpdated);

        return () => {
            try {
                channel.stopListening(EVENT, onUpdated);
            } catch {
                // Echo may already be torn down during reconnect.
            }

            echo.leave(CHANNEL);
        };
    }, [echoGeneration]);
}
