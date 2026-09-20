import { Link } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    driverOrderStatusLabel,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { cn } from '@/lib/utils';
import { chat as orderChat, show as orderShow } from '@/routes/driver/orders';

type DriverOrderCardProps = {
    order: DriverAssignedOrder;
};

export function DriverOrderCard({ order }: DriverOrderCardProps) {
    const isDelivered = order.status === 'delivered';

    return (
        <article className="bg-accent flex flex-col gap-3 rounded-xl p-4 sm:p-5">
            <Link
                href={orderShow.url(order.id)}
                prefetch
                className="flex flex-col gap-3 transition-opacity hover:opacity-95"
            >
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0 space-y-1">
                        <p className="text-muted-foreground text-sm font-medium">
                            Order {order.orderNumber}
                        </p>
                        <h2 className="text-foreground text-xl font-semibold tracking-tight">
                            {order.customerName}
                        </h2>
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        <Badge variant="outline">{order.areaLabel}</Badge>
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
            </Link>

            <Button
                type="button"
                variant="outline"
                size="lg"
                className="h-12 w-full"
                asChild
            >
                <Link
                    href={orderChat.url(order.id)}
                    prefetch
                    aria-label={`Chat about order ${order.orderNumber}`}
                    data-test="driver-order-chat"
                >
                    <MessageCircle data-icon="inline-start" />
                    Chat
                </Link>
            </Button>
        </article>
    );
}
