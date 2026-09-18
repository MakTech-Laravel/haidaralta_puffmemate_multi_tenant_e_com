import { useEffect, useState } from 'react';

import type { InboxUpdatedPayload } from '@/components/conversation/types';
import { getEcho, getEchoGeneration } from '@/echo';

const INBOX_EVENT = '.conversation.inbox-updated';

/**
 * Keep the left-hand conversation list live.
 *
 * Every participant has their own inbox channel, so a thread the operator is
 * not currently looking at still reorders and re-badges the moment someone
 * posts to it.
 */
export function useConversationInboxRealtime(
    channel: string,
    onUpdate: (payload: InboxUpdatedPayload) => void,
): void {
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

        const privateChannel = echo.private(channel);

        privateChannel.listen(INBOX_EVENT, onUpdate);

        return () => {
            try {
                privateChannel.stopListening(INBOX_EVENT, onUpdate);
            } catch {
                // Echo may already be torn down during reconnect.
            }

            echo.leave(channel);
        };
    }, [channel, echoGeneration, onUpdate]);
}
