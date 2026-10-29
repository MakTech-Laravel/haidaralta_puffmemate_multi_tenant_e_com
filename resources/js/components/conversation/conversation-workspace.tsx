import { router } from '@inertiajs/react';
import { MessagesSquare } from 'lucide-react';
import {
    useCallback,
    useEffect,
    useRef,
    useState,
    type ReactNode,
} from 'react';

import { useConversationInboxRealtime } from '@/hooks/use-conversation-inbox-realtime';
import { acknowledgeDelivery } from '@/lib/conversation';
import { cn } from '@/lib/utils';

import { ConversationList } from './conversation-list';
import { ConversationThread } from './conversation-thread';
import { NewConversationDialog } from './new-conversation-dialog';
import type {
    ConversationMessage,
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
    branchOptionsUrl = null,
    kind = 'order',
    title = 'Conversations',
    showSenderRole = false,
    canClose = true,
    optionField = 'order_id',
    threadTitle,
    hideInbox = false,
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
    /** Platform only; branch operators cannot widen their scope. */
    branchOptionsUrl?: string | null;
    kind?: 'order' | 'driver_ops' | 'platform_branch';
    title?: string;
    showSenderRole?: boolean;
    canClose?: boolean;
    /** POST body field for NewConversationDialog (`order_id` or `driver_id` / `branch_id`). */
    optionField?: 'order_id' | 'driver_id' | 'branch_id';
    /** Optional thread header override (e.g. “Platform” on branch portal). */
    threadTitle?: string;
    /** Single-thread portals (driver / branch↔platform) skip the inbox pane. */
    hideInbox?: boolean;
}) {
    const isOpsChat = kind === 'driver_ops' || kind === 'platform_branch';
    const allowClose = canClose && !isOpsChat;
    const showStatusFilter = !isOpsChat;

    const [search, setSearch] = useState(inboxMeta.filters.search);
    const [status, setStatus] = useState(
        isOpsChat ? 'all' : inboxMeta.filters.status,
    );
    const [branchId, setBranchId] = useState(inboxMeta.filters.branch_id);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    // Realtime bumps are client-only overlays on the server's merge-prop list.
    const [overlay, setOverlay] = useState<
        Record<number, Partial<ConversationRow>>
    >({});
    // Threads that arrived over Echo before the next Inertia page of the list.
    const [extras, setExtras] = useState<ConversationRow[]>([]);
    const [incomingMessage, setIncomingMessage] =
        useState<ConversationMessage | null>(null);
    const isFirstRender = useRef(true);
    const conversationsRef = useRef(conversations);

    conversationsRef.current = conversations;

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
            setExtras([]);
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
    // the moment someone posts to it. The same payload also inserts unknown
    // rows and, when the thread is open, forwards the message into the pane.
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

            if (payload.message) {
                setIncomingMessage(payload.message);
            }

            if (payload.row) {
                setExtras((current) => {
                    if (
                        current.some((row) => row.id === payload.row.id) ||
                        conversationsRef.current.some(
                            (row) => row.id === payload.row.id,
                        )
                    ) {
                        return current;
                    }

                    return [payload.row, ...current];
                });
            }

            setOverlay((current) => {
                const previous = current[payload.conversation_id];
                const isOpenHere = payload.conversation_id === activeId;
                const isOwn = payload.sender_id === viewerId;
                const existing = conversationsRef.current.find(
                    (row) => row.id === payload.conversation_id,
                );

                return {
                    ...current,
                    [payload.conversation_id]: {
                        status: payload.status,
                        last_message_preview: payload.last_message_preview,
                        last_message_at: payload.last_message_at,
                        unread_count:
                            isOpenHere || isOwn
                                ? 0
                                : (previous?.unread_count ??
                                      existing?.unread_count ??
                                      0) + 1,
                    },
                };
            });
        },
        [activeId, deliveredUrlFor, viewerId],
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

    const rows = applyOverlay(conversations, overlay, extras).filter((row) =>
        matchesInboxFilters(row, { status, search, branchId }),
    );

    const newChatDialog = (
        <NewConversationDialog
            optionsUrl={orderOptionsUrl}
            storeUrl={storeUrl}
            optionField={optionField}
            dialogTitle={
                kind === 'driver_ops'
                    ? 'New driver chat'
                    : kind === 'platform_branch'
                      ? 'New platform chat'
                      : 'New conversation'
            }
            dialogDescription={
                kind === 'driver_ops'
                    ? 'Pick someone to open a shared chat with.'
                    : kind === 'platform_branch'
                      ? 'Open a shared chat between platform HQ and the branch.'
                      : 'Pick an order that does not have a conversation yet.'
            }
        />
    );

    return (
        <div className="flex h-full min-h-0 gap-4">
            {hideInbox ? null : (
                <aside
                    className={cn(
                        'bg-card min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-xl border lg:flex lg:w-80 xl:w-96',
                        thread === null ? 'flex' : 'hidden',
                    )}
                >
                    <div className="flex items-center justify-between gap-2 border-b px-3 py-2.5">
                        <h1 className="text-base font-semibold">{title}</h1>
                        {newChatDialog}
                    </div>
                    <ConversationList
                        rows={rows}
                        activeId={activeId}
                        search={search}
                        status={status}
                        branchId={branchId}
                        branchLabel={inboxMeta.branchLabel}
                        branchOptionsUrl={branchOptionsUrl}
                        loading={loading}
                        loadingMore={loadingMore}
                        hasMore={inboxMeta.hasMore}
                        hrefFor={hrefFor}
                        onSearchChange={setSearch}
                        onStatusChange={setStatus}
                        onBranchChange={setBranchId}
                        onLoadMore={loadMore}
                        showStatusFilter={showStatusFilter}
                    />
                </aside>
            )}

            <section
                className={cn(
                    'min-h-0 min-w-0 flex-1',
                    hideInbox
                        ? 'block'
                        : cn('lg:block', thread === null ? 'hidden' : 'block'),
                )}
            >
                {thread === null ? (
                    <EmptyThread kind={kind} action={hideInbox ? newChatDialog : null} />
                ) : (
                    <ConversationThread
                        key={thread.conversation.id}
                        {...thread}
                        viewerId={viewerId}
                        viewerName={viewerName}
                        backUrl={indexUrl}
                        incomingMessage={incomingMessage}
                        showSenderRole={showSenderRole}
                        canClose={allowClose && thread.canClose}
                        headerTitle={threadTitle}
                        showBackLink={!hideInbox}
                        showStatusBadge={!isOpsChat}
                    />
                )}
            </section>
        </div>
    );
}

/**
 * Newly-active rows float to the top, matching how a messenger reorders when a
 * message lands. Unknown ids from Echo are prepended so a thread opened on the
 * other portal appears without a reload.
 */
function applyOverlay(
    rows: ConversationRow[],
    overlay: Record<number, Partial<ConversationRow>>,
    extras: ConversationRow[],
): ConversationRow[] {
    if (extras.length === 0 && Object.keys(overlay).length === 0) {
        return rows;
    }

    const byId = new Map<number, ConversationRow>();

    for (const row of rows) {
        byId.set(row.id, row);
    }

    for (const extra of extras) {
        if (!byId.has(extra.id)) {
            byId.set(extra.id, extra);
        }
    }

    return [...byId.values()]
        .map((row) => ({ ...row, ...overlay[row.id] }))
        .sort((a, b) => sortKey(b) - sortKey(a));
}

function matchesInboxFilters(
    row: ConversationRow,
    filters: { status: string; search: string; branchId: string },
): boolean {
    if (filters.status !== 'all' && row.status !== filters.status) {
        return false;
    }

    if (
        filters.branchId !== 'all' &&
        String(row.branch?.id ?? '') !== filters.branchId
    ) {
        return false;
    }

    const needle = filters.search.trim().toLowerCase();

    if (needle === '') {
        return true;
    }

    return (
        (row.title ?? row.order_number ?? '')
            .toLowerCase()
            .includes(needle) ||
        (row.order_number ?? '').toLowerCase().includes(needle) ||
        (row.last_message_preview ?? '').toLowerCase().includes(needle)
    );
}

function sortKey(row: ConversationRow): number {
    return row.last_message_at ? Date.parse(row.last_message_at) : 0;
}

function EmptyThread({
    kind,
    action = null,
}: {
    kind: 'order' | 'driver_ops' | 'platform_branch';
    action?: ReactNode;
}) {
    const hint =
        action !== null
            ? kind === 'platform_branch'
                ? 'Start a chat with platform headquarters.'
                : 'Start a chat with your branch.'
            : kind === 'order'
              ? 'Pick a thread on the left, or open one for an order.'
              : 'Pick a thread on the left, or start a new chat.';

    return (
        <div className="bg-card flex h-full flex-col items-center justify-center gap-3 rounded-xl border px-6 text-center">
            <div className="bg-muted flex size-14 items-center justify-center rounded-full">
                <MessagesSquare className="text-muted-foreground size-7" />
            </div>
            <div>
                <p className="font-medium">
                    {action !== null
                        ? 'No chat yet'
                        : 'No conversation selected'}
                </p>
                <p className="text-muted-foreground text-sm">{hint}</p>
            </div>
            {action}
        </div>
    );
}
