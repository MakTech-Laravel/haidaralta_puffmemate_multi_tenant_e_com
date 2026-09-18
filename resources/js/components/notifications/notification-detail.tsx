import { NotificationUnreadDot } from '@/components/notifications/notification-read-status';
import { NotificationTypeIcon } from '@/components/notifications/notification-type-icon';
import type { NotificationItem } from '@/components/notifications/types';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type NotificationDetailProps = {
    notification: NotificationItem;
    className?: string;
};

export function NotificationDetail({
    notification,
    className,
}: NotificationDetailProps) {
    return (
        <article
            className={cn(
                'space-y-4 rounded-xl p-4 sm:p-6',
                notification.read
                    ? 'bg-accent'
                    : 'bg-primary/5 ring-primary/30 ring-1',
                className,
            )}
        >
            <header className="flex items-start gap-3">
                <NotificationTypeIcon
                    type={notification.type}
                    className="size-12"
                    iconClassName="size-6"
                />
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        {!notification.read ? <NotificationUnreadDot /> : null}
                        <h1
                            className={cn(
                                'text-xl tracking-tight sm:text-2xl',
                                notification.read
                                    ? 'text-muted-foreground font-semibold'
                                    : 'text-foreground font-bold',
                            )}
                        >
                            {notification.title}
                        </h1>
                        <Badge
                            variant={notification.read ? 'secondary' : 'default'}
                            className="rounded-sm"
                        >
                            {notification.read ? 'Read' : 'Unread'}
                        </Badge>
                    </div>
                    <time className="text-muted-foreground text-sm">
                        {notification.createdAt}
                    </time>
                </div>
            </header>
            <p className="text-foreground text-base leading-relaxed whitespace-pre-wrap">
                {notification.body ?? notification.description}
            </p>
        </article>
    );
}
