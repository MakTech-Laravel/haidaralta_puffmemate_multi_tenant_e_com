import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ConversationComposer } from '@/components/conversation/conversation-composer';
import { ConversationMessageRow } from '@/components/conversation/conversation-message-row';
import { daySeparatorLabel } from '@/components/conversation/format';
import type {
    ConversationMessage,
    MessageCreatedPayload,
    ReceiptState,
} from '@/components/conversation/types';
import { useConversationRealtime } from '@/hooks/use-conversation-realtime';

function sameDay(a: string | null, b: string | null): boolean {
    if (!a || !b) {
        return a === b;
    }

    return new Date(a).toDateString() === new Date(b).toDateString();
}

function typingLabel(names: string[]): string {
    if (names.length === 1) {
        return `${names[0]} is typing…`;
    }

    if (names.length === 2) {
        return `${names[0]} and ${names[1]} are typing…`;
    }

    return 'Several people are typing…';
}

let nextClientId = 1;

type OrderChatBodyProps = {
    messages: ConversationMessage[];
    /** Presence channel of the order thread; null when no thread is open. */
    channel: string | null;
    viewerId: number;
    viewerName: string;
    canSend: boolean;
    messageUrl: string;
    intro: string;
};

/**
 * Transcript + composer for one order thread (driver and customer portals).
 *
 * Sends go through an Inertia POST, which reloads `messages`; peers' messages
 * arrive live on the order's presence channel.
 */
export function OrderChatBody({
    messages: initialMessages,
    channel,
    viewerId,
    viewerName,
    canSend,
    messageUrl,
    intro,
}: OrderChatBodyProps) {
    const [messages, setMessages] = useState(initialMessages);
    const [closed, setClosed] = useState(false);
    const viewportRef = useRef<HTMLDivElement>(null);
    const conversationId = initialMessages[0]?.conversation_id ?? 0;
    const sendEnabled = canSend && !closed;

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    const scrollToBottom = useCallback(() => {
        const viewport = viewportRef.current;

        if (viewport) {
            viewport.scrollTop = viewport.scrollHeight;
        }
    }, []);

    useEffect(() => {
        requestAnimationFrame(scrollToBottom);
    }, [messages.length, scrollToBottom]);

    const onMessage = useCallback(
        ({ message }: MessageCreatedPayload) => {
            // The viewer's own sends come back through the Inertia reload.
            if (message.sender?.id === viewerId) {
                return;
            }

            setMessages((current) =>
                current.some((one) => one.id === message.id)
                    ? current
                    : [...current, message],
            );
        },
        [viewerId],
    );

    const onClosed = useCallback(() => setClosed(true), []);
    const onReceipts = useCallback(() => undefined, []);

    const { typing, notifyTyping } = useConversationRealtime({
        channel,
        viewerId,
        viewerName,
        onMessage,
        onClosed,
        onReceipts,
    });

    const onSend = useCallback(
        (body: string, files: File[]): boolean => {
            if (!sendEnabled || files.length > 0) {
                return false;
            }

            const clientId = -nextClientId++;

            setMessages((current) => [
                ...current,
                {
                    id: clientId,
                    conversation_id: conversationId,
                    type: 'text',
                    body,
                    created_at: new Date().toISOString(),
                    sender: {
                        id: viewerId,
                        name: viewerName,
                        avatar_url: null,
                    },
                    attachments: [],
                    pending: {
                        clientId,
                        state: 'sending',
                        body,
                        files: [],
                    },
                },
            ]);

            router.post(
                messageUrl,
                { body },
                {
                    preserveScroll: true,
                    onError: () => {
                        setMessages((current) =>
                            current.map((one) =>
                                one.id === clientId && one.pending
                                    ? {
                                          ...one,
                                          pending: {
                                              ...one.pending,
                                              state: 'failed',
                                          },
                                      }
                                    : one,
                            ),
                        );
                    },
                },
            );

            return true;
        },
        [sendEnabled, conversationId, messageUrl, viewerId, viewerName],
    );

    const discard = useCallback((clientId: number) => {
        setMessages((current) => current.filter((one) => one.id !== clientId));
    }, []);

    const rows = useMemo(
        () =>
            messages.map((message, index) => {
                const previous = messages[index - 1];
                const isOwn = message.sender?.id === viewerId;

                let receipt: ReceiptState | null = null;

                if (isOwn && message.type !== 'system') {
                    receipt = message.pending
                        ? message.pending.state
                        : 'sent';
                }

                return {
                    message,
                    isOwn,
                    receipt,
                    daySeparator:
                        previous === undefined ||
                        !sameDay(previous.created_at, message.created_at)
                            ? daySeparatorLabel(message.created_at)
                            : null,
                    showHeader:
                        previous === undefined ||
                        previous.sender?.id !== message.sender?.id ||
                        previous.type === 'system',
                };
            }),
        [messages, viewerId],
    );

    return (
        <>
            <div
                ref={viewportRef}
                role="log"
                aria-live="polite"
                aria-label="Order chat transcript"
                className="min-h-0 flex-1 overflow-y-auto overscroll-contain"
            >
                <div className="flex min-h-full flex-col justify-end gap-1 px-3 py-4 sm:px-4">
                    <p className="text-muted-foreground pb-2 text-center text-xs">
                        {intro}
                    </p>

                    {rows.map(
                        ({
                            message,
                            isOwn,
                            showHeader,
                            receipt,
                            daySeparator,
                        }) => (
                            <div key={message.id}>
                                {daySeparator ? (
                                    <div className="sticky top-0 z-10 flex justify-center py-2">
                                        <span className="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-medium">
                                            {daySeparator}
                                        </span>
                                    </div>
                                ) : null}
                                <ConversationMessageRow
                                    message={message}
                                    isOwn={isOwn}
                                    showHeader={showHeader}
                                    receipt={receipt}
                                    onDiscard={() => discard(message.id)}
                                />
                            </div>
                        ),
                    )}
                </div>
            </div>

            {typing.length > 0 ? (
                <p className="text-muted-foreground px-3 pb-1 text-xs sm:px-4">
                    {typingLabel(typing.map((one) => one.name))}
                </p>
            ) : null}

            <ConversationComposer
                disabled={!sendEnabled}
                onSend={onSend}
                onTyping={notifyTyping}
            />
        </>
    );
}
