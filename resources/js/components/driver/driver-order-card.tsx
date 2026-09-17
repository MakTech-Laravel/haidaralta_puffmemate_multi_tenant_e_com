import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    driverOrderStatusLabel,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';

type DriverOrderCardProps = {
    order: DriverAssignedOrder;
};

export function DriverOrderCard({ order }: DriverOrderCardProps) {
    const isDelivered = order.status === 'delivered';

    return (
        <Link
            href={orderShow.url(order.id)}
            prefetch
            className="bg-accent block rounded-xl p-4 transition-opacity hover:opacity-95 sm:p-5"
        >
            <article className="flex flex-col gap-3">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0 space-y-1">
                        <p className="text-muted-foreground text-sm font-medium">
                            Order {order.orderNumber}
                        </p>
                        <h2 className="text-foreground text-xl font-semibold tracking-tight">
                            {order.customerName}
                        </h2>
                    </div>
                    <Badge
                        className={cn(
                            'rounded-sm px-2.5 py-0.5 text-base font-medium',
                            isDelivered
                                ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                : 'bg-primary text-primary-foreground border-transparent',
                        )}
                    >
                        {driverOrderStatusLabel[order.status]}
                    </Badge>
                </div>

                <p className="text-muted-foreground text-base leading-snug">
                    {order.addressSummary}
                </p>

                <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    <span>{order.windowLabel}</span>
                    <span>
                        {order.productCount}{' '}
                        {order.productCount === 1 ? 'item' : 'items'}
                    </span>
                    <span className="text-foreground font-medium">
                        {order.totalLabel}
                    </span>
                </div>
            </article>
        </Link>
    );
}
