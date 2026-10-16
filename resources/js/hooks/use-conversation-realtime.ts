import { useCallback, useEffect, useRef, useState } from 'react';

import type {
    ConversationClosedPayload,
    MessageCreatedPayload,
    PresenceMember,
    ReceiptsUpdatedPayload,
    TypingWhisper,
} from '@/components/conversation/types';
import { getEcho, getEchoGeneration } from '@/echo';

const MESSAGE_EVENT = '.conversation.message-created';
const CLOSED_EVENT = '.conversation.closed';
const RECEIPTS_EVENT = '.conversation.receipts-updated';
const TYPING_WHISPER = 'typing';

/** How long a peer stays in the "typing…" line after their last keystroke. */
const TYPING_TTL_MS = 3000;

type Options = {
    /** Null skips the subscription (no thread opened yet). */
    channel: string | null;
    viewerId: number;
    viewerName: string;
    onMessage: (payload: MessageCreatedPayload) => void;
    onClosed: (payload: ConversationClosedPayload) => void;
    onReceipts: (payload: ReceiptsUpdatedPayload) => void;
};

type Result = {
    /** Everyone with the thread open right now, the viewer excluded. */
    online: PresenceMember[];
    /** Peers who pressed a key in the last few seconds. */
    typing: PresenceMember[];
    /** Tell peers the viewer is composing. Safe to call on every keystroke. */
    notifyTyping: () => void;
};

/**
 * Subscribe to one order thread on Reverb.
 *
 * The channel is a presence channel, so a single subscription carries new
 * messages, the roster of who is watching, and `typing` whispers. It is
 * page-scoped and leaves on unmount, unlike the global private user channel
 * which must stay subscribed for toasts.
 */
export function useConversationRealtime({
    channel,
    viewerId,
    viewerName,
    onMessage,
    onClosed,
    onReceipts,
}: Options): Result {
    const [echoGeneration, setEchoGeneration] = useState(() =>
        getEchoGeneration(),
    );
    const [online, setOnline] = useState<PresenceMember[]>([]);
    const [typing, setTyping] = useState<PresenceMember[]>([]);
    const whisperRef = useRef<
        ((event: string, data: TypingWhisper) => void) | null
    >(null);
    const lastWhisperAt = useRef(0);

    useEffect(() => {
        const onReconnected = () => setEchoGeneration(getEchoGeneration());

        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, []);

    useEffect(() => {
        const echo = getEcho();

        if (!echo || channel === null) {
            return;
        }

        const withoutViewer = (members: PresenceMember[]) =>
            members.filter((member) => member.id !== viewerId);

        const presence = echo.join(channel);

        whisperRef.current = (event, data) => presence.whisper(event, data);

        presence.here((members: PresenceMember[]) =>
            setOnline(withoutViewer(members)),
        );
        presence.joining((member: PresenceMember) =>
            setOnline((current) =>
                withoutViewer([
                    ...current.filter((one) => one.id !== member.id),
                    member,
                ]),
            ),
        );
        presence.leaving((member: PresenceMember) => {
            setOnline((current) =>
                current.filter((one) => one.id !== member.id),
            );
            setTyping((current) =>
                current.filter((one) => one.id !== member.id),
            );
        });
        presence.error((error: unknown) => {
            if (import.meta.env.DEV) {
                console.error('[echo] conversation presence', channel, error);
            }
        });
        presence.listen(MESSAGE_EVENT, (payload: MessageCreatedPayload) => {
            // A delivered message ends that sender's typing indicator.
            setTyping((current) =>
                current.filter((one) => one.id !== payload.message.sender?.id),
            );
            onMessage(payload);
        });
        presence.listen(CLOSED_EVENT, onClosed);
        presence.listen(RECEIPTS_EVENT, onReceipts);
        presence.listenForWhisper(TYPING_WHISPER, (peer: TypingWhisper) => {
            if (peer.id === viewerId) {
                return;
            }

            setTyping((current) => [
                ...current.filter((one) => one.id !== peer.id),
                { id: peer.id, name: peer.name, avatar_url: null },
            ]);

            window.setTimeout(() => {
                setTyping((current) =>
                    current.filter((one) => one.id !== peer.id),
                );
            }, TYPING_TTL_MS);
        });

        return () => {
            whisperRef.current = null;
            setOnline([]);
            setTyping([]);

            try {
                presence.stopListening(MESSAGE_EVENT);
                presence.stopListening(CLOSED_EVENT);
                presence.stopListening(RECEIPTS_EVENT);
            } catch {
                // Echo may already be torn down during reconnect.
            }

            echo.leave(channel);
        };
    }, [channel, echoGeneration, viewerId, onMessage, onClosed, onReceipts]);

    // Throttled so a fast typist sends one whisper per second, not one per key.
    const notifyTyping = useCallback(() => {
        const now = Date.now();

        if (now - lastWhisperAt.current < 1000) {
            return;
        }

        lastWhisperAt.current = now;
        whisperRef.current?.(TYPING_WHISPER, {
            id: viewerId,
            name: viewerName,
        });
    }, [viewerId, viewerName]);

    return { online, typing, notifyTyping };
}
