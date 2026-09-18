import { cn } from '@/lib/utils';

type NotificationUnreadDotProps = {
    className?: string;
};

export function NotificationUnreadDot({
    className,
}: NotificationUnreadDotProps) {
    return (
        <span
            className={cn('bg-primary size-2 shrink-0 rounded-full', className)}
            aria-hidden
        />
    );
}

type NotificationReadLabelProps = {
    read: boolean;
    className?: string;
};

export function NotificationReadLabel({
    read,
    className,
}: NotificationReadLabelProps) {
    return (
        <span
            className={cn(
                'text-xs font-medium',
                read ? 'text-muted-foreground' : 'text-primary',
                className,
            )}
        >
            {read ? 'Read' : 'Unread'}
        </span>
    );
}
