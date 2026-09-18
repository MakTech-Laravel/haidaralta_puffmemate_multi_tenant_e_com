import {
    Bell,
    CreditCard,
    Package,
    Shield,
    Tag,
    Truck,
    type LucideIcon,
} from 'lucide-react';
import type { NotificationType } from '@/components/notifications/types';
import { cn } from '@/lib/utils';

const iconByType: Record<NotificationType, LucideIcon> = {
    order: Package,
    payment: CreditCard,
    delivery: Truck,
    promo: Tag,
    security: Shield,
};

type NotificationTypeIconProps = {
    type?: NotificationType | string | null;
    className?: string;
    iconClassName?: string;
};

export function NotificationTypeIcon({
    type,
    className,
    iconClassName,
}: NotificationTypeIconProps) {
    const Icon =
        type && type in iconByType
            ? iconByType[type as NotificationType]
            : Bell;

    return (
        <span
            className={cn(
                'bg-muted text-muted-foreground inline-flex size-10 shrink-0 items-center justify-center rounded-lg',
                className,
            )}
            aria-hidden
        >
            <Icon className={cn('size-5', iconClassName)} strokeWidth={1.75} />
        </span>
    );
}
