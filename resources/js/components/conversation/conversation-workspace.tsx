import { router } from '@inertiajs/react';
import { MessagesSquare } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { useConversationInboxRealtime } from '@/hooks/use-conversation-inbox-realtime';
import { acknowledgeDelivery } from '@/lib/conversation';
import { cn } from '@/lib/utils';

import { ConversationList } from './conversation-list';
import { ConversationThread } from './conversation-thread';
import { NewConversationDialog } from './new-conversation-dialog';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
    InboxUpdatedPayload,
} from './types';

const SEARCH_DEBOUNCE_MS = 250;

/** Both list props travel together on every partial reload. */
const INBOX_PROPS = ['conversations', 'inboxMeta'];

/**
 * Two-pane messenger: inbox on the left, transcript on the right.
 *
 * Both the index and show routes render this same component, so opening a
 * thread swaps only the right pane — the list keeps its scroll position,
 * search term and realtime subscription across visits.
 *
 * Below `lg` the two panes become one: the list is the page until a thread is
 * open, and the thread then takes over with a back control in its header.
 */
export function ConversationWorkspace({
    conversations,
    inboxMeta,
    thread,
    viewerId,
    viewerName,
    indexUrl,
    inboxChannel,
    orderOptionsUrl,
    storeUrl,
    hrefFor,
    deliveredUrlFor,
}: {
    conversations: ConversationRow[];
    inboxMeta: InboxMeta;
    thread: ConversationThreadProps | null;
    viewerId: number;
    viewerName: string;
    indexUrl: string;
    inboxChannel: string;
    orderOptionsUrl: string;
    storeUrl: string;
    hrefFor: (row: ConversationRow) => string;
    deliveredUrlFor: (conversationId: number) => string;
}) {
    const [search, setSearch] = useState(inboxMeta.filters.search);
    const [status, setStatus] = useState(inboxMeta.filters.status);
    const [branchId, setBranchId] = useState(inboxMeta.filters.branch_id);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    // Realtime bumps are client-only overlays on the server's merge-prop list.
    const [overlay, setOverlay] = useState<
        Record<number, Partial<ConversationRow>>
    >({});
    const isFirstRender = useRef(true);

    const activeId = thread?.conversation.id ?? null;

    // Debounced filter reload. `reset` clears the merged list so a new search
    // starts from page one instead of appending to the previous results.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            setLoading(true);
            setOverlay({});
            router.reload({
                only: INBOX_PROPS,
                reset: ['conversations'],
                data: {
                    search,
                    status,
                    branch_id: branchId,
                    cursor: undefined,
                },
                onFinish: () => setLoading(false),
            });
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timer);
    }, [search, status, branchId]);

    const loadMore = useCallback(() => {
        if (
            loadingMore ||
            !inboxMeta.hasMore ||
            inboxMeta.nextCursor === null
        ) {
            return;
        }

        setLoadingMore(true);
        router.reload({
            only: INBOX_PROPS,
            data: {
                search,
                status,
                branch_id: branchId,
                cursor: inboxMeta.nextCursor,
            },
            onFinish: () => setLoadingMore(false),
        });
    }, [
        branchId,
        inboxMeta.hasMore,
        inboxMeta.nextCursor,
        loadingMore,
        search,
        status,
    ]);

    // A thread the operator is not looking at still re-badges and re-previews
    // the moment someone posts to it.
    const onInboxUpdate = useCallback(
        (payload: InboxUpdatedPayload) => {
            // The thread being open handles its own ack; this covers the case
            // where the operator is elsewhere in the inbox but still received
            // the message, which is exactly what "delivered" means.
            if (
                payload.conversation_id !== activeId &&
                payload.last_message_id > 0
            ) {
                void acknowledgeDelivery(
                    deliveredUrlFor(payload.conversation_id),
                    payload.last_message_id,
                );
            }

            setOverlay((current) => {
                const previous = current[payload.conversation_id];
                const isOpenHere = payload.conversation_id === activeId;

                return {
                    ...current,
                    [payload.conversation_id]: {
                        status: payload.status,
                        last_message_preview: payload.last_message_preview,
                        last_message_at: payload.last_message_at,
                        unread_count: isOpenHere
                            ? 0
                            : (previous?.unread_count ?? 0) + 1,
                    },
                };
            });
        },
        [activeId, deliveredUrlFor],
    );

    useConversationInboxRealtime(inboxChannel, onInboxUpdate);

    // Opening a thread clears its badge without waiting for the server.
    useEffect(() => {
        if (activeId === null) {
            return;
        }

        setOverlay((current) => ({
            ...current,
            [activeId]: { ...current[activeId], unread_count: 0 },
        }));
    }, [activeId, thread?.messages.length]);

    const rows = applyOverlay(conversations, overlay);

    return (
        <div className="flex h-full min-h-0 gap-4">
            <aside
                className={cn(
                    'bg-card min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-xl border lg:flex lg:w-80 xl:w-96',
                    thread === null ? 'flex' : 'hidden',
                )}
            >
                <div className="flex items-center justify-between gap-2 border-b px-3 py-2.5">
                    <h1 className="text-base font-semibold">Conversations</h1>
                    <NewConversationDialog
                        optionsUrl={orderOptionsUrl}
                        storeUrl={storeUrl}
                    />
                </div>
                <ConversationList
                    rows={rows}
                    activeId={activeId}
                    search={search}
                    status={status}
                    branchId={branchId}
                    branches={inboxMeta.branches}
                    loading={loading}
                    loadingMore={loadingMore}
                    hasMore={inboxMeta.hasMore}
                    hrefFor={hrefFor}
                    onSearchChange={setSearch}
                    onStatusChange={setStatus}
                    onBranchChange={setBranchId}
                    onLoadMore={loadMore}
                />
            </aside>

            <section
                className={cn(
                    'min-h-0 min-w-0 flex-1 lg:block',
                    thread === null ? 'hidden' : 'block',
                )}
            >
                {thread === null ? (
                    <EmptyThread />
                ) : (
                    <ConversationThread
                        key={thread.conversation.id}
                        {...thread}
                        viewerId={viewerId}
                        viewerName={viewerName}
                        backUrl={indexUrl}
                    />
                )}
            </section>
        </div>
    );
}

/**
 * Newly-active rows float to the top, matching how a messenger reorders when a
 * message lands.
 */
function applyOverlay(
    rows: ConversationRow[],
    overlay: Record<number, Partial<ConversationRow>>,
): ConversationRow[] {
    if (Object.keys(overlay).length === 0) {
        return rows;
    }

    return rows
        .map((row) => ({ ...row, ...overlay[row.id] }))
        .sort((a, b) => sortKey(b) - sortKey(a));
}

function sortKey(row: ConversationRow): number {
    return row.last_message_at ? Date.parse(row.last_message_at) : 0;
}

function EmptyThread() {
    return (
        <div className="bg-card flex h-full flex-col items-center justify-center gap-3 rounded-xl border px-6 text-center">
            <div className="bg-muted flex size-14 items-center justify-center rounded-full">
                <MessagesSquare className="text-muted-foreground size-7" />
            </div>
            <div>
                <p className="font-medium">No conversation selected</p>
                <p className="text-muted-foreground text-sm">
                    Pick a thread on the left, or open one for an order.
                </p>
            </div>
        </div>
    );
}
