import { Link } from '@inertiajs/react';
import { MessagesSquare, Search } from 'lucide-react';
import { useEffect, useMemo, useRef } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

import { relativeTime } from './format';
import type { ConversationRow, InboxParticipant } from './types';

const STATUS_TABS = [
    { value: 'open', label: 'Open' },
    { value: 'all', label: 'All' },
    { value: 'closed', label: 'Closed' },
] as const;

export function ConversationList({
    rows,
    activeId,
    search,
    status,
    branchId,
    branchLabel,
    branchOptionsUrl,
    loading,
    loadingMore,
    hasMore,
    hrefFor,
    onSearchChange,
    onStatusChange,
    onBranchChange,
    onLoadMore,
}: {
    rows: ConversationRow[];
    activeId: number | null;
    search: string;
    status: string;
    branchId: string;
    branchLabel: string | null;
    branchOptionsUrl: string | null;
    loading: boolean;
    loadingMore: boolean;
    hasMore: boolean;
    hrefFor: (row: ConversationRow) => string;
    onSearchChange: (value: string) => void;
    onStatusChange: (value: string) => void;
    onBranchChange: (value: string) => void;
    onLoadMore: () => void;
}) {
    const sentinel = useRef<HTMLDivElement>(null);

    // Infinite scroll: the list pages on `last_message_at`, never page numbers.
    useEffect(() => {
        const node = sentinel.current;

        if (!node || !hasMore) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    onLoadMore();
                }
            },
            { rootMargin: '200px' },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [hasMore, onLoadMore]);

    return (
        <div className="flex h-full min-h-0 flex-col">
            <div className="space-y-3 border-b p-3">
                <div className="relative">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        placeholder="Search orders, people, messages…"
                        className="pl-9"
                        aria-label="Search conversations"
                    />
                </div>
                <div
                    role="tablist"
                    aria-label="Filter conversations"
                    className="bg-muted flex gap-1 rounded-lg p-1"
                >
                    {STATUS_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            role="tab"
                            aria-selected={status === tab.value}
                            onClick={() => onStatusChange(tab.value)}
                            className={cn(
                                'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                status === tab.value
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Branch operators are already scoped by route, so the URL is
                    only supplied on the platform portal. Options page in as you
                    scroll rather than shipping every branch in the props. */}
                {branchOptionsUrl ? (
                    <InfiniteScrollCombobox
                        id="conversation-branch-filter"
                        optionsUrl={branchOptionsUrl}
                        value={branchId === 'all' ? null : branchId}
                        onValueChange={(value) =>
                            onBranchChange(value ?? 'all')
                        }
                        selectedLabel={branchLabel}
                        placeholder="All branches"
                        searchPlaceholder="Search branches…"
                        emptyText="No branches with conversations."
                        noneLabel="All branches"
                        descriptionKey={null}
                    />
                ) : null}
            </div>

            {/*
             * A plain scroller, not ScrollArea: the Radix viewport wraps its
             * child in a `display: table` element, which makes the flex child
             * grow to the text width and defeats `truncate` on the preview.
             */}
            <div className="min-h-0 flex-1 scrollbar-thin overflow-y-auto overscroll-contain">
                <div className="p-2">
                    {loading ? <ConversationListSkeleton /> : null}

                    {!loading && rows.length === 0 ? (
                        <div className="text-muted-foreground flex flex-col items-center gap-2 px-6 py-16 text-center">
                            <MessagesSquare className="size-8" />
                            <p className="text-sm">
                                {search.trim() === ''
                                    ? 'No conversations yet.'
                                    : `Nothing matches “${search.trim()}”.`}
                            </p>
                        </div>
                    ) : null}

                    {!loading
                        ? rows.map((row) => (
                              <ConversationListItem
                                  key={row.id}
                                  row={row}
                                  href={hrefFor(row)}
                                  active={row.id === activeId}
                              />
                          ))
                        : null}

                    <div ref={sentinel} aria-hidden />

                    {loadingMore ? (
                        <div className="flex justify-center py-4">
                            <Spinner />
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

function ConversationListItem({
    row,
    href,
    active,
}: {
    row: ConversationRow;
    href: string;
    active: boolean;
}) {
    const counterpart = useMemo(() => primaryParticipant(row), [row]);
    const unread = row.unread_count > 0;

    return (
        <Link
            href={href}
            prefetch="hover"
            preserveState
            preserveScroll
            only={['thread']}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex w-full min-w-0 items-start gap-3 overflow-hidden rounded-lg px-3 py-2.5 transition-colors',
                active ? 'bg-muted' : 'hover:bg-muted/60',
            )}
        >
            <Avatar className="size-10 shrink-0">
                {counterpart?.avatar_url ? (
                    <AvatarImage
                        src={counterpart.avatar_url}
                        alt={counterpart.name}
                    />
                ) : null}
                <AvatarFallback>{initials(counterpart?.name)}</AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1 overflow-hidden">
                <div className="flex min-w-0 items-baseline gap-2">
                    <span
                        className={cn(
                            'truncate text-sm',
                            unread ? 'font-semibold' : 'font-medium',
                        )}
                    >
                        {row.order_number}
                    </span>
                    <span className="text-muted-foreground ml-auto shrink-0 text-xs">
                        {relativeTime(row.last_message_at)}
                    </span>
                </div>

                <div className="flex min-w-0 items-center gap-2">
                    <p
                        className={cn(
                            'min-w-0 flex-1 truncate text-sm',
                            unread
                                ? 'text-foreground font-medium'
                                : 'text-muted-foreground',
                        )}
                    >
                        {row.last_message_preview ?? 'No messages yet'}
                    </p>
                    {unread ? (
                        <Badge className="h-5 min-w-5 shrink-0 justify-center rounded-full px-1.5 text-xs tabular-nums">
                            {row.unread_count > 99 ? '99+' : row.unread_count}
                        </Badge>
                    ) : null}
                </div>

                <p className="text-muted-foreground truncate text-xs">
                    {[row.branch?.name, row.order_status_label]
                        .filter(Boolean)
                        .join(' · ')}
                    {row.status === 'closed' ? ' · Closed' : ''}
                </p>
            </div>
        </Link>
    );
}

function ConversationListSkeleton() {
    return (
        <div className="space-y-1" aria-hidden>
            {Array.from({ length: 8 }).map((_, index) => (
                <div
                    key={index}
                    className="flex items-center gap-3 px-3 py-2.5"
                >
                    <Skeleton className="size-10 shrink-0 rounded-full" />
                    <div className="flex-1 space-y-2">
                        <Skeleton className="h-3.5 w-24" />
                        <Skeleton className="h-3 w-full" />
                    </div>
                </div>
            ))}
        </div>
    );
}

/**
 * The inbox row shows the customer where there is one, so operators recognise
 * the thread by the person rather than by the order number alone.
 */
function primaryParticipant(
    row: ConversationRow,
): InboxParticipant | undefined {
    return (
        row.participants.find(
            (participant) => participant.role_label === 'Customer',
        ) ?? row.participants[0]
    );
}

function initials(name: string | undefined): string {
    if (!name) {
        return '??';
    }

    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
