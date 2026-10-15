import { Badge } from '@/components/ui/badge';
import { orderSourceBadgeClass } from '@/lib/order-badges';
import { cn } from '@/lib/utils';

type OrderSourceBadgeProps = {
    source: string;
    label: string;
    className?: string;
};

export function OrderSourceBadge({
    source,
    label,
    className,
}: OrderSourceBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(orderSourceBadgeClass(source), className)}
        >
            {label}
        </Badge>
    );
}
