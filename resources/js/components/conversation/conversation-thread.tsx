import { Link, router } from '@inertiajs/react';
import { ChevronLeft, Lock, MessageSquareDashed } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerItem,
    MessageScrollerProvider,
    MessageScrollerViewport,
} from '@/components/ui/message-scroller';
import { Spinner } from '@/components/ui/spinner';
import { useConversationRealtime } from '@/hooks/use-conversation-realtime';
import {
    fetchOlderMessages,
    markConversationRead,
    sendMessage,
} from '@/lib/conversation';
import { ConversationComposer } from './conversation-composer';
import { ConversationMessageRow } from './conversation-message-row';
import type {
    ConversationEndpoints,
    ConversationMessage,
    ConversationSummary,
} from './types';

/**
 * Merge a message into the transcript, keeping it ordered by id and ignoring
 * duplicates. Both the optimistic send and the Reverb echo can deliver the same
 * row, and history loads prepend a whole page at once.
 */
function mergeMessages(
    current: ConversationMessage[],
    incoming: ConversationMessage[],
): ConversationMessage[] {
    const byId = new Map(current.map((message) => [message.id, message]));

    incoming.forEach((message) => byId.set(message.id, message));

    return [...byId.values()].sort((a, b) => a.id - b.id);
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
}) {
    const [messages, setMessages] = useState(initialMessages);
    const [hasMore, setHasMore] = useState(initialHasMore);
    const [loadingOlder, setLoadingOlder] = useState(false);
    const [sending, setSending] = useState(false);
    const [status, setStatus] = useState(conversation.status);
    const lastReadRef = useRef(0);

    const isClosed = status === 'closed';

    const onMessage = useCallback(
        ({ message }: { message: ConversationMessage }) => {
            setMessages((current) => mergeMessages(current, [message]));
        },
        [],
    );

    const onClosed = useCallback(() => {
        setStatus('closed');
        router.reload({ only: ['thread'] });
    }, []);

    const { online, typing, notifyTyping } = useConversationRealtime({
        channel: conversation.channel,
        viewerId,
        viewerName,
        onMessage,
        onClosed,
    });

    const newestId = messages.length > 0 ? messages[messages.length - 1].id : 0;

    // Keep the server-side read marker in step with what has actually rendered.
    useEffect(() => {
        if (newestId === 0 || newestId <= lastReadRef.current) {
            return;
        }

        lastReadRef.current = newestId;
        void markConversationRead(endpoints.read, newestId);
    }, [newestId, endpoints.read]);

    const loadOlder = useCallback(async () => {
        if (loadingOlder || !hasMore || messages.length === 0) {
            return;
        }

        setLoadingOlder(true);

        try {
            const page = await fetchOlderMessages(
                endpoints.messages,
                messages[0].id,
            );

            setMessages((current) => mergeMessages(current, page.messages));
            setHasMore(page.hasMore);
        } catch {
            toast.error('Could not load earlier messages.');
        } finally {
            setLoadingOlder(false);
        }
    }, [endpoints.messages, hasMore, loadingOlder, messages]);

    const onSend = useCallback(
        async (body: string, files: File[]): Promise<boolean> => {
            setSending(true);

            try {
                const message = await sendMessage(endpoints.send, body, files);

                setMessages((current) => mergeMessages(current, [message]));

                return true;
            } catch (error) {
                toast.error(
                    error instanceof Error
                        ? error.message
                        : 'Could not send the message.',
                );

                return false;
            } finally {
                setSending(false);
            }
        },
        [endpoints.send],
    );

    const rows = useMemo(
        () =>
            messages.map((message, index) => {
                const previous = messages[index - 1];

                return {
                    message,
                    isOwn: message.sender?.id === viewerId,
                    // Start a fresh avatar + name block whenever the sender changes.
                    showHeader:
                        previous === undefined ||
                        previous.sender?.id !== message.sender?.id ||
                        previous.type === 'system',
                };
            }),
        [messages, viewerId],
    );

    return (
        <div className="bg-card flex h-full min-h-0 flex-col overflow-hidden rounded-xl border">
            <header className="flex flex-wrap items-center justify-between gap-3 border-b px-3 py-3 sm:px-4">
                <div className="flex min-w-0 items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        asChild
                        className="lg:hidden"
                    >
                        <Link href={backUrl} aria-label="Back to conversations">
                            <ChevronLeft />
                        </Link>
                    </Button>
                    <div className="min-w-0">
                        <div className="flex items-center gap-2">
                            <h2 className="truncate font-semibold">
                                Order{' '}
                                {conversation.order?.order_number ?? 'unknown'}
                            </h2>
                            <Badge variant={isClosed ? 'secondary' : 'default'}>
                                {isClosed ? 'Closed' : 'Open'}
                            </Badge>
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
                                    {conversation.branch?.name ?? 'No branch'} ·{' '}
                                    {conversation.order?.status_label ?? '—'} ·{' '}
                                    {conversation.participants.length}{' '}
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
                        onClick={() =>
                            router.post(
                                endpoints.close,
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <Lock />
                        Close conversation
                    </Button>
                ) : null}
            </header>

            <MessageScrollerProvider
                autoScroll
                defaultScrollPosition="last-anchor"
                scrollPreviousItemPeek={64}
            >
                <MessageScroller className="min-h-0 flex-1">
                    <MessageScrollerViewport>
                        <MessageScrollerContent className="px-4 py-4">
                            {hasMore ? (
                                <MessageScrollerItem messageId="load-earlier">
                                    <div className="flex justify-center pb-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            disabled={loadingOlder}
                                            onClick={() => void loadOlder()}
                                        >
                                            {loadingOlder ? <Spinner /> : null}
                                            Load earlier messages
                                        </Button>
                                    </div>
                                </MessageScrollerItem>
                            ) : null}

                            {rows.length === 0 ? (
                                <div className="text-muted-foreground flex flex-col items-center gap-2 py-16 text-center">
                                    <MessageSquareDashed className="size-8" />
                                    <p className="text-sm">
                                        No messages yet. Say hello to get the
                                        thread started.
                                    </p>
                                </div>
                            ) : null}

                            {rows.map(({ message, isOwn, showHeader }) => (
                                <MessageScrollerItem
                                    key={message.id}
                                    messageId={String(message.id)}
                                    scrollAnchor={showHeader}
                                >
                                    <ConversationMessageRow
                                        message={message}
                                        isOwn={isOwn}
                                        showHeader={showHeader}
                                    />
                                </MessageScrollerItem>
                            ))}
                        </MessageScrollerContent>
                    </MessageScrollerViewport>
                    <MessageScrollerButton />
                </MessageScroller>
            </MessageScrollerProvider>

            <ConversationComposer
                disabled={!canSend || isClosed}
                sending={sending}
                onSend={onSend}
                onTyping={notifyTyping}
            />
        </div>
    );
}
