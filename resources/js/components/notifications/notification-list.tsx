import { Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { NotificationLinkButton } from '@/components/notifications/notification-link-button';
import {
    NotificationReadLabel,
    NotificationUnreadDot,
} from '@/components/notifications/notification-read-status';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import type { NotificationItem } from '@/components/notifications/types';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';

type NotificationFilter = 'all' | 'read' | 'unread';

type NotificationListProps = {
    items: NotificationItem[];
    getItemHref: (item: NotificationItem) => string;
    onDelete?: (item: NotificationItem) => void;
    className?: string;
};

export function NotificationList({
    items,
    getItemHref,
    onDelete,
    className,
}: NotificationListProps) {
    const [filter, setFilter] = useState<NotificationFilter>('all');

    const filteredItems = items.filter((item) => {
        if (filter === 'read') {
            return item.read;
        }

        if (filter === 'unread') {
            return !item.read;
        }

        return true;
    });

    return (
        <div className={cn('flex flex-col gap-4', className)}>
            <ToggleGroup
                type="single"
                value={filter}
                onValueChange={(value) => {
                    if (
                        value === 'all' ||
                        value === 'read' ||
                        value === 'unread'
                    ) {
                        setFilter(value);
                    }
                }}
                variant="outline"
                size="sm"
                className="justify-start"
                aria-label="Filter notifications"
            >
                <ToggleGroupItem value="all" aria-label="All notifications">
                    All
                </ToggleGroupItem>
                <ToggleGroupItem value="read" aria-label="Read notifications">
                    Read
                </ToggleGroupItem>
                <ToggleGroupItem
                    value="unread"
                    aria-label="Unread notifications"
                >
                    Unread
                </ToggleGroupItem>
            </ToggleGroup>

            {filteredItems.length === 0 ? (
                <div className="text-muted-foreground rounded-xl border border-dashed px-4 py-12 text-center text-sm">
                    No notifications
                </div>
            ) : (
                <ul className="flex flex-col gap-3">
                    {filteredItems.map((item) => (
                        <li key={item.id}>
                            <div
                                className={cn(
                                    'flex items-start gap-3 rounded-xl p-4 transition-colors',
                                    item.read
                                        ? 'bg-accent'
                                        : 'bg-primary/5 ring-primary/30 ring-1',
                                )}
                            >
                                <NotificationTypeIcon
                                    type={item.type}
                                    icon={item.icon}
                                />

                                <Link
                                    href={getItemHref(item)}
                                    prefetch
                                    className="min-w-0 flex-1 hover:opacity-90"
                                >
                                    <div className="flex items-center gap-2">
                                        {!item.read ? (
                                            <NotificationUnreadDot />
                                        ) : null}
                                        <p
                                            className={cn(
                                                'text-base tracking-tight',
                                                item.read
                                                    ? 'text-muted-foreground font-medium'
                                                    : 'text-foreground font-bold',
                                            )}
                                        >
                                            {item.title}
                                        </p>
                                    </div>
                                    <p className="text-muted-foreground mt-1 line-clamp-2 text-sm">
                                        {item.description}
                                    </p>
                                </Link>

                                <div className="flex shrink-0 flex-col items-end gap-1">
                                    <time className="text-muted-foreground text-xs">
                                        {item.createdAt}
                                    </time>
                                    <NotificationReadLabel read={item.read} />
                                    {item.href ? (
                                        <NotificationLinkButton
                                            href={item.href}
                                            label={item.linkLabel}
                                            size="sm"
                                            onClick={(event) => {
                                                event.stopPropagation();
                                            }}
                                        />
                                    ) : null}
                                    {onDelete ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="text-muted-foreground hover:text-destructive size-8"
                                            aria-label={`Delete ${item.title}`}
                                            onClick={(event) => {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                onDelete(item);
                                            }}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
