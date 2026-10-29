import { Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ChevronLeft,
    Lock,
    MessageSquareDashed,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useChatScroll } from '@/hooks/use-chat-scroll';
import { useConversationRealtime } from '@/hooks/use-conversation-realtime';
import {
    acknowledgeDelivery,
    fetchOlderMessages,
    markConversationRead,
    sendMessage,
} from '@/lib/conversation';

import { ConversationComposer } from './conversation-composer';
import { ConversationMessageRow } from './conversation-message-row';
import { daySeparatorLabel } from './format';
import { receiptStateFor } from './message-receipt';
import type {
    ConversationEndpoints,
    ConversationMessage,
    ConversationSummary,
    ParticipantReceipt,
    ReceiptState,
} from './types';

/**
 * Merge messages into the transcript, ordered by id and deduplicated. Both the
 * optimistic send and the Reverb echo can deliver the same row, and history
 * loads prepend a whole page at once.
 *
 * Optimistic rows carry negative ids, which sorts them after everything real —
 * exactly where a message being sent belongs.
 */
function mergeMessages(
    current: ConversationMessage[],
    incoming: ConversationMessage[],
): ConversationMessage[] {
    const byId = new Map(current.map((message) => [message.id, message]));

    incoming.forEach((message) => byId.set(message.id, message));

    return [...byId.values()].sort(sortForTranscript);
}

function sortForTranscript(
    a: ConversationMessage,
    b: ConversationMessage,
): number {
    if (a.pending && !b.pending) {
        return 1;
    }

    if (b.pending && !a.pending) {
        return -1;
    }

    return a.id - b.id;
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

function sameDay(a: string | null, b: string | null): boolean {
    if (!a || !b) {
        return a === b;
    }

    return new Date(a).toDateString() === new Date(b).toDateString();
}

function dayLabel(iso: string | null): string {
    return daySeparatorLabel(iso);
}

let nextClientId = 1;

export function ConversationThread({
    conversation,
    messages: initialMessages,
    hasMore: initialHasMore,
    viewerId,
    viewerName,
    canSend,
    canClose,
    endpoints,
    backUrl,
    showSenderRole = false,
    incomingMessage = null,
    headerTitle,
    showBackLink = true,
    showStatusBadge = true,
    viewerLastReadMessageId = null,
}: {
    conversation: ConversationSummary;
    messages: ConversationMessage[];
    hasMore: boolean;
    viewerId: number;
    viewerName: string;
    canSend: boolean;
    canClose: boolean;
    endpoints: ConversationEndpoints;
    backUrl: string;
    showSenderRole?: boolean;
    /**
     * Inbox-channel fallback. Presence MessageCreated is still the primary
     * path; this covers the case where the operator is in the thread but the
     * presence join never completed.
     */
    incomingMessage?: ConversationMessage | null;
    /** Optional override for the thread heading (portal-specific labels). */
    headerTitle?: string;
    /** Hide when the portal has no inbox list (single-thread layout). */
    showBackLink?: boolean;
    /** Order chats keep Open/Closed; ops chats do not. */
    showStatusBadge?: boolean;
    /** Captured before markRead — first peer message after this shows Unread. */
    viewerLastReadMessageId?: number | null;
}) {
    const [messages, setMessages] = useState(initialMessages);
    const [hasMore, setHasMore] = useState(initialHasMore);
    const [loadingOlder, setLoadingOlder] = useState(false);
    const [localClosed, setLocalClosed] = useState(false);
    const [closing, setClosing] = useState(false);
    const [receipts, setReceipts] = useState<ParticipantReceipt[]>(
        conversation.receipts,
    );

    const lastReadRef = useRef(0);
    const lastAckRef = useRef(0);
    const unreadCutoffRef = useRef(viewerLastReadMessageId ?? 0);

    /**
     * `router.post` preserves this component's state, so status cannot live
     * only in useState — the Inertia payload is the source of truth, with a
     * local latch for the click and for Echo events that beat the response.
     */
    const isClosed = conversation.status === 'closed' || localClosed;

    const onMessage = useCallback(
        ({ message }: { message: ConversationMessage }) => {
            setMessages((current) => mergeMessages(current, [message]));
        },
        [],
    );

    const onClosed = useCallback(() => {
        setLocalClosed(true);
    }, []);

    const onReceipts = useCallback(
        ({ receipts: incoming }: { receipts: ParticipantReceipt[] }) => {
            setReceipts(incoming);
        },
        [],
    );

    const { online, typing, notifyTyping } = useConversationRealtime({
        channel: conversation.channel,
        viewerId,
        viewerName,
        onMessage,
        onClosed,
        onReceipts,
    });

    useEffect(() => {
        setMessages((current) => mergeMessages(current, initialMessages));
        setHasMore(initialHasMore);
    }, [initialMessages, initialHasMore]);

    useEffect(() => {
        if (
            incomingMessage === null ||
            incomingMessage.conversation_id !== conversation.id ||
            incomingMessage.sender?.id === viewerId
        ) {
            return;
        }

        setMessages((current) => mergeMessages(current, [incomingMessage]));
    }, [incomingMessage, conversation.id, viewerId]);

    useEffect(() => {
        setReceipts(conversation.receipts);
    }, [conversation.receipts]);

    const closeThread = (): void => {
        if (isClosed || closing) {
            return;
        }

        setClosing(true);
        setLocalClosed(true);

        router.post(
            endpoints.close,
            {},
            {
                preserveScroll: true,
                only: ['thread', 'conversations', 'inboxMeta'],
                onError: () => setLocalClosed(false),
                onFinish: () => setClosing(false),
            },
        );
    };

    const persisted = useMemo(
        () => messages.filter((message) => !message.pending),
        [messages],
    );

    const newestId =
        persisted.length > 0 ? persisted[persisted.length - 1].id : 0;
    const oldestId = persisted.length > 0 ? persisted[0].id : 0;

    // Ack delivery first, then the read marker: a thread on screen is read.
    useEffect(() => {
        if (newestId === 0) {
            return;
        }

        if (newestId > lastAckRef.current) {
            lastAckRef.current = newestId;
            void acknowledgeDelivery(endpoints.delivered, newestId);
        }

        if (newestId > lastReadRef.current) {
            lastReadRef.current = newestId;
            void markConversationRead(endpoints.read, newestId);
        }
    }, [newestId, endpoints.delivered, endpoints.read]);

    const loadOlder = useCallback(async () => {
        if (loadingOlder || !hasMore || oldestId === 0) {
            return;
        }

        setLoadingOlder(true);

        try {
            const page = await fetchOlderMessages(endpoints.messages, oldestId);

            setMessages((current) => mergeMessages(current, page.messages));
            setHasMore(page.hasMore);
        } catch {
            toast.error('Could not load earlier messages.');
        } finally {
            setLoadingOlder(false);
        }
    }, [endpoints.messages, hasMore, loadingOlder, oldestId]);

    const { viewportRef, topSentinelRef, showJumpToLatest, scrollToBottom } =
        useChatScroll({
            newestId:
                messages.length > 0 ? messages[messages.length - 1].id : 0,
            oldestId,
            hasMore,
            onLoadOlder: () => void loadOlder(),
        });

    /**
     * Push the optimistic row, then reconcile. The composer never blocks and
     * never spins: the feedback lives on the bubble.
     */
    const deliver = useCallback(
        async (clientId: number, body: string, files: File[]) => {
            try {
                const message = await sendMessage(endpoints.send, body, files);

                setMessages((current) =>
                    mergeMessages(
                        current.filter((one) => one.id !== clientId),
                        [message],
                    ),
                );
            } catch (error) {
                setMessages((current) =>
                    current.map((one) =>
                        one.id === clientId && one.pending
                            ? {
                                  ...one,
                                  pending: { ...one.pending, state: 'failed' },
                              }
                            : one,
                    ),
                );

                if (error instanceof Error) {
                    toast.error(error.message);
                }
            }
        },
        [endpoints.send],
    );

    const onSend = useCallback(
        (body: string, files: File[]): boolean => {
            const clientId = -nextClientId++;

            setMessages((current) =>
                mergeMessages(current, [
                    {
                        id: clientId,
                        conversation_id: conversation.id,
                        type: 'text',
                        body,
                        created_at: new Date().toISOString(),
                        sender: {
                            id: viewerId,
                            name: viewerName,
                            avatar_url: null,
                        },
                        attachments: [],
                        pending: { clientId, state: 'sending', body, files },
                    },
                ]),
            );

            scrollToBottom();
            void deliver(clientId, body, files);

            return true;
        },
        [conversation.id, deliver, scrollToBottom, viewerId, viewerName],
    );

    const retry = useCallback(
        (clientId: number) => {
            setMessages((current) =>
                current.map((one) =>
                    one.id === clientId && one.pending
                        ? {
                              ...one,
                              pending: { ...one.pending, state: 'sending' },
                          }
                        : one,
                ),
            );

            const failed = messages.find((one) => one.id === clientId);

            if (failed?.pending) {
                void deliver(
                    clientId,
                    failed.pending.body,
                    failed.pending.files,
                );
            }
        },
        [deliver, messages],
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
                    daySeparator:
                        previous === undefined ||
                        !sameDay(previous.created_at, message.created_at)
                            ? dayLabel(message.created_at)
                            : null,
                    // Start a fresh avatar + name block whenever the sender changes.
                    showHeader:
                        previous === undefined ||
                        previous.sender?.id !== message.sender?.id ||
                        previous.type === 'system',
                };
            }),
        [messages, receipts, viewerId, firstUnreadId],
    );

    return (
        <div className="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-xl border">
            <header className="flex flex-wrap items-center justify-between gap-3 border-b px-3 py-3 sm:px-4">
                <div className="flex min-w-0 flex-1 items-center gap-2">
                    {showBackLink ? (
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            className="lg:hidden"
                        >
                            <Link
                                href={backUrl}
                                aria-label="Back to conversations"
                            >
                                <ChevronLeft />
                            </Link>
                        </Button>
                    ) : null}
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <h2 className="truncate font-semibold">
                                {headerTitle ??
                                    (conversation.type === 'driver_ops'
                                        ? conversation.driver?.id === viewerId
                                            ? (conversation.branch?.name ??
                                              'Branch chat')
                                            : (conversation.driver?.name ??
                                              conversation.branch?.name ??
                                              'Driver chat')
                                        : conversation.type ===
                                            'platform_branch'
                                          ? (conversation.branch?.name ??
                                            'Branch chat')
                                          : `Order ${conversation.order?.order_number ?? 'unknown'}`)}
                            </h2>
                            {showStatusBadge ? (
                                <Badge
                                    variant={
                                        isClosed ? 'secondary' : 'default'
                                    }
                                >
                                    {isClosed ? 'Closed' : 'Open'}
                                </Badge>
                            ) : null}
                        </div>
                        <p className="text-muted-foreground truncate text-sm">
                            {typing.length > 0 ? (
                                <span className="text-foreground">
                                    {typingLabel(typing.map((one) => one.name))}
                                </span>
                            ) : (
                                <>
                                    {online.length > 0 ? (
                                        <span className="mr-1 inline-flex items-center gap-1.5">
                                            <span
                                                className="inline-block size-2 rounded-full bg-emerald-500"
                                                aria-hidden
                                            />
                                            {online.length} online ·
                                        </span>
                                    ) : null}
                                    {conversation.branch?.name ?? 'No branch'}
                                    {conversation.type === 'driver_ops' ||
                                    conversation.type === 'platform_branch'
                                        ? null
                                        : ` · ${conversation.order?.status_label ?? '—'}`}{' '}
                                    · {conversation.participants.length}{' '}
                                    participant
                                    {conversation.participants.length === 1
                                        ? ''
                                        : 's'}
                                </>
                            )}
                        </p>
                    </div>
                </div>

                {canClose && !isClosed ? (
                    <Button
                        variant="outline"
                        disabled={closing}
                        onClick={closeThread}
                    >
                        <Lock />
                        Close conversation
                    </Button>
                ) : null}
            </header>

            <div className="relative min-h-0 flex-1">
                <div
                    ref={viewportRef}
                    role="log"
                    aria-live="polite"
                    aria-label="Conversation transcript"
                    className="h-full scrollbar-thin overflow-y-auto overscroll-contain"
                >
                    {/*
                     * `justify-end` inside a full-height track is what keeps a
                     * short thread sitting on the composer instead of floating
                     * with dead space beneath it.
                     */}
                    <div className="flex min-h-full flex-col justify-end gap-1 px-4 py-4">
                        <div ref={topSentinelRef} aria-hidden />

                        {loadingOlder ? (
                            <div className="flex justify-center py-3">
                                <Spinner />
                            </div>
                        ) : null}

                        {!hasMore && rows.length > 0 ? (
                            <p className="text-muted-foreground py-2 text-center text-xs">
                                This is the beginning of the conversation.
                            </p>
                        ) : null}

                        {rows.length === 0 ? (
                            <div className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center">
                                <MessageSquareDashed className="size-8" />
                                <p className="text-sm">
                                    No messages yet. Say hello to get the thread
                                    started.
                                </p>
                            </div>
                        ) : null}

                        {rows.map(
                            ({
                                message,
                                isOwn,
                                showHeader,
                                receipt,
                                daySeparator,
                                showUnreadDivider,
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
                                    <ConversationMessageRow
                                        message={message}
                                        isOwn={isOwn}
                                        showHeader={showHeader}
                                        receipt={receipt}
                                        showSenderRole={showSenderRole}
                                        onRetry={() => retry(message.id)}
                                        onDiscard={() => discard(message.id)}
                                    />
                                </div>
                            ),
                        )}
                    </div>
                </div>

                {showJumpToLatest ? (
                    <Button
                        size="icon"
                        variant="secondary"
                        aria-label="Jump to latest message"
                        onClick={() => scrollToBottom('smooth')}
                        className="absolute right-4 bottom-4 rounded-full shadow-md"
                    >
                        <ArrowDown />
                    </Button>
                ) : null}
            </div>

            <ConversationComposer
                disabled={!canSend || isClosed}
                onSend={onSend}
                onTyping={notifyTyping}
            />
        </div>
    );
}
