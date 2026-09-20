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
};

export function DriverOrderCard({
    order,
    highlighted = false,
}: DriverOrderCardProps) {
    const isDelivered = order.status === 'delivered';
    const productPreview = order.productPreview.join(' · ');

    return (
        <article
            className={cn(
                'bg-accent flex flex-col gap-3 rounded-xl p-4 sm:p-5',
                highlighted && 'ring-primary ring-2',
            )}
        >
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

                <div className="space-y-1">
                    <p className="text-muted-foreground text-base leading-snug">
                        {order.addressSummary}
                    </p>
                    {order.unitLabel ? (
                        <p className="text-muted-foreground text-sm">
                            {order.unitLabel}
                        </p>
                    ) : null}
                </div>

                <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    <span>{order.windowLabel}</span>
                    {order.etaLabel ? <span>ETA {order.etaLabel}</span> : null}
                    <span>
                        {order.productCount}{' '}
                        {order.productCount === 1 ? 'item' : 'items'}
                    </span>
                    <span className="text-foreground font-medium">
                        {order.totalLabel}
                    </span>
                    <span>
                        {order.paymentMethod === 'cod' ? 'Cash on delivery' : 'Paid'}
                    </span>
                </div>

                {productPreview ? (
                    <p className="text-muted-foreground truncate text-sm">
                        {productPreview}
                        {order.productCount > order.productPreview.length
                            ? ' · + more'
                            : ''}
                    </p>
                ) : null}

                {order.notes ? (
                    <p className="text-foreground text-sm leading-snug">
                        Note: {order.notes}
                    </p>
                ) : null}
            </Link>

            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    className="h-12 min-w-0 flex-1"
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
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    className="size-12 shrink-0"
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
                        <MapPin className="size-5" />
                    </Link>
                </Button>
            </div>
        </article>
    );
}
