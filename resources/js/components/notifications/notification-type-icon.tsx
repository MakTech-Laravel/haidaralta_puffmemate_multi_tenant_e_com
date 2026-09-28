import {
    Bell,
    CreditCard,
    Info,
    Megaphone,
    Package,
    RefreshCw,
    Shield,
    Tag,
    Truck,
    type LucideIcon,
} from 'lucide-react';
import type { NotificationType } from '@/components/notifications/types';
import { Icon } from '@/lib/icons';
import { cn } from '@/lib/utils';

const iconByType: Record<NotificationType, LucideIcon> = {
    order: Package,
    payment: CreditCard,
    delivery: Truck,
    promo: Tag,
    security: Shield,
    info: Info,
    alert: Megaphone,
    update: RefreshCw,
};

type NotificationTypeIconProps = {
    type?: NotificationType | null;
    icon?: string | null;
    className?: string;
    iconClassName?: string;
};

export function NotificationTypeIcon({
    type,
    icon,
    className,
    iconClassName,
}: NotificationTypeIconProps) {
    const FallbackIcon = type ? iconByType[type] : Bell;

    return (
        <span
            className={cn(
                'bg-muted text-muted-foreground inline-flex size-10 shrink-0 items-center justify-center rounded-lg',
                className,
            )}
            aria-hidden
        >
            {icon ? (
                <Icon
                    icon={icon}
                    className={cn('size-5', iconClassName)}
                    strokeWidth={1.75}
                />
            ) : (
                <FallbackIcon
                    className={cn('size-5', iconClassName)}
                    strokeWidth={1.75}
                />
            )}
        </span>
    );
}
