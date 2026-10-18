import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import {
    NotificationReadLabel,
    NotificationUnreadDot,
} from '@/components/notifications/notification-read-status';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import type { NotificationItem } from '@/components/notifications/types';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type NotificationsPopoverProps = {
    items: NotificationItem[];
    viewAllHref: string;
    getItemHref: (item: NotificationItem) => string;
    isGuest?: boolean;
    guestHref?: string;
    className?: string;
    triggerClassName?: string;
};

export function NotificationsPopover({
    items,
    viewAllHref,
    getItemHref,
    isGuest = false,
    guestHref,
    className,
    triggerClassName,
}: NotificationsPopoverProps) {
    const [open, setOpen] = useState(false);
    const previewItems = items.slice(0, 5);
    const unreadCount = items.filter((item) => !item.read).length;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={cn(
                        'text-foreground relative size-10',
                        triggerClassName,
                    )}
                    aria-label="Notifications"
                    aria-expanded={open}
                    data-test="notifications-bell"
                >
                    <Bell className="size-6 md:size-8" strokeWidth={1.75} />
                    {unreadCount > 0 ? (
                        <span className="bg-primary absolute top-1.5 right-1.5 size-2 rounded-full" />
                    ) : null}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="end"
                side="bottom"
                sideOffset={8}
                collisionPadding={2}
                className={cn(
                    'w-[min(100vw-1.5rem,24rem)] max-w-[calc(100vw-1.5rem)] p-0',
                    'max-sm:w-[calc(100vw-4px)] max-sm:max-w-none',
                    className,
                )}
            >
                <div className="px-3 py-2.5">
                    <p className="text-sm font-medium">Notifications</p>
                    <p className="text-muted-foreground text-xs">
                        Order updates and account alerts appear here.
                    </p>
                </div>

                {isGuest ? (
                    <div className="text-muted-foreground border-t px-3 py-6 text-center text-sm">
                        <p>Sign in to view notifications.</p>
                        {guestHref ? (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="mt-2"
                                asChild
                            >
                                <Link
                                    href={guestHref}
                                    prefetch
                                    onClick={() => setOpen(false)}
                                >
                                    Sign in
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                ) : previewItems.length === 0 ? (
                    <div className="text-muted-foreground border-t px-3 py-8 text-center text-sm">
                        No notifications
                    </div>
                ) : (
                    <ul className="max-h-80 overflow-y-auto border-t">
                        {previewItems.map((item) => (
                            <li
                                key={item.id}
                                className="border-b last:border-b-0"
                            >
                                <Link
                                    href={getItemHref(item)}
                                    prefetch
                                    className={cn(
                                        'flex items-start gap-3 px-3 py-2.5 transition-colors',
                                        item.read
                                            ? 'hover:bg-muted/60'
                                            : 'bg-primary/5 hover:bg-primary/10',
                                    )}
                                    onClick={() => setOpen(false)}
                                >
                                    <NotificationTypeIcon
                                        type={item.type}
                                        icon={item.icon}
                                        className="size-9"
                                        iconClassName="size-4"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            {!item.read ? (
                                                <NotificationUnreadDot />
                                            ) : null}
                                            <p
                                                className={cn(
                                                    'min-w-0 truncate text-sm',
                                                    item.read
                                                        ? 'text-muted-foreground font-medium'
                                                        : 'text-foreground font-bold',
                                                )}
                                            >
                                                {item.title}
                                            </p>
                                            <NotificationReadLabel
                                                read={item.read}
                                                className="ml-auto shrink-0"
                                            />
                                        </div>
                                        <p className="text-muted-foreground mt-0.5 line-clamp-2 text-xs">
                                            {item.description}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                {!isGuest ? (
                    <div className="border-t p-1.5">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full"
                            asChild
                        >
                            <Link
                                href={viewAllHref}
                                prefetch
                                onClick={() => setOpen(false)}
                            >
                                View all
                            </Link>
                        </Button>
                    </div>
                ) : null}
            </PopoverContent>
        </Popover>
    );
}
