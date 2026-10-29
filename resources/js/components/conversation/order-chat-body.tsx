import { router } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ConversationComposer } from '@/components/conversation/conversation-composer';
import { ConversationMessageRow } from '@/components/conversation/conversation-message-row';
import { daySeparatorLabel } from '@/components/conversation/format';
import { receiptStateFor } from '@/components/conversation/message-receipt';
import type {
    ConversationMessage,
    MessageCreatedPayload,
    ParticipantReceipt,
    ReceiptState,
    ReceiptsUpdatedPayload,
} from '@/components/conversation/types';
import { Button } from '@/components/ui/button';
import { useChatScroll } from '@/hooks/use-chat-scroll';
import { useConversationRealtime } from '@/hooks/use-conversation-realtime';
import {
    acknowledgeDelivery,
    markConversationRead,
} from '@/lib/conversation';
import { cn } from '@/lib/utils';

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
    receipts?: ParticipantReceipt[];
    deliveredUrl?: string | null;
    readUrl?: string | null;
    /** Captured before server markRead — drives the Unread divider. */
    viewerLastReadMessageId?: number | null;
};

/**
 * Transcript + composer for one order thread (driver and customer portals).
 *
 * Sends go through an Inertia POST, which reloads `messages`; peers' messages
 * arrive live on the order's presence channel. Delivery/read acks match
 * ConversationThread so ticks update for both sides.
 */
export function OrderChatBody({
    messages: initialMessages,
    channel,
    viewerId,
    viewerName,
    canSend,
    messageUrl,
    intro,
    receipts: initialReceipts = [],
    deliveredUrl = null,
    readUrl = null,
    viewerLastReadMessageId = null,
}: OrderChatBodyProps) {
    const [messages, setMessages] = useState(initialMessages);
    const [receipts, setReceipts] = useState(initialReceipts);
    const [closed, setClosed] = useState(false);
    const [highlightedIds, setHighlightedIds] = useState<Set<number>>(
        () => new Set(),
    );
    const conversationId = initialMessages[0]?.conversation_id ?? 0;
    const sendEnabled = canSend && !closed;
    const lastAckRef = useRef(0);
    const lastReadRef = useRef(0);
    const unreadCutoffRef = useRef(viewerLastReadMessageId ?? 0);

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    useEffect(() => {
        setReceipts(initialReceipts);
    }, [initialReceipts]);

    useEffect(() => {
        unreadCutoffRef.current = viewerLastReadMessageId ?? 0;
    }, [viewerLastReadMessageId]);

    const persisted = useMemo(
        () => messages.filter((message) => !message.pending),
        [messages],
    );
    const newestId =
        persisted.length > 0 ? persisted[persisted.length - 1].id : 0;
    const oldestId = persisted.length > 0 ? persisted[0].id : 0;

    const { viewportRef, showJumpToLatest, scrollToBottom } = useChatScroll({
        newestId,
        oldestId,
        hasMore: false,
        onLoadOlder: () => undefined,
    });

    useEffect(() => {
        if (newestId === 0) {
            return;
        }

        if (deliveredUrl && newestId > lastAckRef.current) {
            lastAckRef.current = newestId;
            void acknowledgeDelivery(deliveredUrl, newestId);
        }

        if (readUrl && newestId > lastReadRef.current) {
            lastReadRef.current = newestId;
            void markConversationRead(readUrl, newestId);
        }
    }, [newestId, deliveredUrl, readUrl]);

    const flashMessage = useCallback((messageId: number) => {
        setHighlightedIds((current) => {
            const next = new Set(current);
            next.add(messageId);

            return next;
        });

        window.setTimeout(() => {
            setHighlightedIds((current) => {
                const next = new Set(current);
                next.delete(messageId);

                return next;
            });
        }, 1600);
    }, []);

    const onMessage = useCallback(
        ({ message }: MessageCreatedPayload) => {
            if (message.sender?.id === viewerId) {
                return;
            }

            setMessages((current) => {
                if (current.some((one) => one.id === message.id)) {
                    return current;
                }

                flashMessage(message.id);

                return [...current, message];
            });
        },
        [viewerId, flashMessage],
    );

    const onClosed = useCallback(() => setClosed(true), []);

    const onReceipts = useCallback(({ receipts: incoming }: ReceiptsUpdatedPayload) => {
        setReceipts(incoming);
    }, []);

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

    const firstUnreadId = useMemo(() => {
        const cutoff = unreadCutoffRef.current;

        if (cutoff <= 0) {
            return null;
        }

        const first = messages.find(
            (message) =>
                !message.pending &&
                message.type !== 'system' &&
                message.sender?.id !== viewerId &&
                message.id > cutoff,
        );

        return first?.id ?? null;
    }, [messages, viewerId]);

    const rows = useMemo(
        () =>
            messages.map((message, index) => {
                const previous = messages[index - 1];
                const isOwn = message.sender?.id === viewerId;

                let receipt: ReceiptState | null = null;

                if (isOwn && message.type !== 'system') {
                    receipt = message.pending
                        ? message.pending.state
                        : receiptStateFor(message.id, viewerId, receipts);
                }

                return {
                    message,
                    isOwn,
                    receipt,
                    showUnreadDivider: message.id === firstUnreadId,
                    highlighted: highlightedIds.has(message.id),
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
        [messages, viewerId, receipts, firstUnreadId, highlightedIds],
    );

    return (
        <>
            <div className="relative min-h-0 flex-1">
                <div
                    ref={viewportRef}
                    role="log"
                    aria-live="polite"
                    aria-label="Order chat transcript"
                    className="h-full overflow-y-auto overscroll-contain"
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
                                showUnreadDivider,
                                highlighted,
                            }) => (
                                <div key={message.id}>
                                    {daySeparator ? (
                                        <div className="sticky top-0 z-10 flex justify-center py-2">
                                            <span className="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-medium">
                                                {daySeparator}
                                            </span>
                                        </div>
                                    ) : null}
                                    {showUnreadDivider ? (
                                        <div className="flex items-center gap-3 py-3">
                                            <div className="bg-border h-px flex-1" />
                                            <span className="text-primary text-xs font-medium tracking-wide uppercase">
                                                Unread messages
                                            </span>
                                            <div className="bg-border h-px flex-1" />
                                        </div>
                                    ) : null}
                                    <div
                                        className={cn(
                                            'rounded-lg transition-colors duration-700',
                                            highlighted && 'bg-primary/10',
                                        )}
                                    >
                                        <ConversationMessageRow
                                            message={message}
                                            isOwn={isOwn}
                                            showHeader={showHeader}
                                            receipt={receipt}
                                            onDiscard={() =>
                                                discard(message.id)
                                            }
                                        />
                                    </div>
                                </div>
                            ),
                        )}
                    </div>
                </div>

                {showJumpToLatest ? (
                    <Button
                        type="button"
                        size="sm"
                        variant="secondary"
                        className="absolute bottom-3 left-1/2 z-10 -translate-x-1/2 shadow-md"
                        onClick={() => scrollToBottom('smooth')}
                    >
                        <ChevronDown className="size-4" />
                        New messages
                    </Button>
                ) : null}
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
