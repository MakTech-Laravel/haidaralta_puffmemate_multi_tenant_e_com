import { Link } from '@inertiajs/react';
import { MapPin, MessageCircle } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    driverOrderStatusLabel,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { cn } from '@/lib/utils';
import { chat as orderChat, show as orderShow } from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';

type DriverOrderCardProps = {
    order: DriverAssignedOrder;
    highlighted?: boolean;
    showRoutePin?: boolean;
};

export function DriverOrderCard({
    order,
    highlighted = false,
    showRoutePin = true,
}: DriverOrderCardProps) {
    const isDelivered = order.status === 'delivered';
    const addressLine = [order.addressSummary, order.unitLabel]
        .filter(Boolean)
        .join(' · ');
    const meta = [
        order.orderNumber,
        order.completedLabel ?? order.windowLabel,
        order.totalLabel,
        order.paymentMethod === 'cod' ? 'COD' : null,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <article
            className={cn(
                'bg-accent flex items-start gap-2 rounded-xl p-3 sm:p-4',
                highlighted && 'ring-primary ring-2',
            )}
        >
            <Link
                href={orderShow.url(order.id)}
                prefetch
                className="flex min-w-0 flex-1 flex-col gap-0.5 transition-opacity hover:opacity-95"
            >
                <div className="flex items-center gap-2">
                    <h2 className="text-foreground min-w-0 truncate text-sm font-semibold tracking-tight sm:text-base">
                        {order.customerName}
                    </h2>
                    <Badge
                        className={cn(
                            'shrink-0 rounded-sm px-1.5 py-0 text-[11px] font-medium',
                            isDelivered
                                ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                : 'bg-primary text-primary-foreground border-transparent',
                        )}
                    >
                        {driverOrderStatusLabel[order.status]}
                    </Badge>
                </div>
                <p className="text-muted-foreground truncate text-sm">
                    {addressLine}
                </p>
                <p className="text-muted-foreground truncate text-xs">
                    {meta}
                </p>
            </Link>

            <div className="flex shrink-0 items-center gap-0.5">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    asChild
                >
                    <Link
                        href={orderChat.url(order.id)}
                        prefetch
                        aria-label={`Chat about order ${order.orderNumber}`}
                        data-test="driver-order-chat"
                    >
                        <MessageCircle className="size-4" />
                    </Link>
                </Button>
                {showRoutePin ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        asChild
                    >
                        <Link
                            href={routeIndex.url({
                                query: { highlight: order.id },
                            })}
                            prefetch
                            aria-label={`Show ${order.customerName} on the route map`}
                            data-test="driver-order-map"
                        >
                            <MapPin className="size-4" />
                        </Link>
                    </Button>
                ) : null}
            </div>
        </article>
    );
}
