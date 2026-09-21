import { Head, Link } from '@inertiajs/react';
import { MessageCircle, Package, Phone } from 'lucide-react';
import { DriverDeliverOtpForm } from '@/components/driver/driver-deliver-otp-form';
import {
    driverMapsQuery,
    driverOrderStatusLabel,
    type DriverOrderDetail,
} from '@/components/driver/driver-order-types';
import { DriverRouteMapPanel } from '@/components/driver/driver-route-map-panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { usePaymentGatewayLabel } from '@/lib/payment-gateway';
import { cn } from '@/lib/utils';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';
import { index as routeIndex } from '@/routes/driver/route';

function mapsSearchUrl(query: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
}

type DriverOrderShowProps = {
    order: DriverOrderDetail;
};

export default function DriverOrderShow({ order }: DriverOrderShowProps) {
    const isDelivered = order.status === 'delivered';
    const paymentLabel = usePaymentGatewayLabel(
        order.paymentMethod,
        order.paymentMethod === 'cod' ? 'Cash on delivery' : 'Paid',
    );

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-2">
                        <p className="text-muted-foreground text-sm font-medium">
                            Order {order.orderNumber}
                        </p>
                        <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            {order.customerName}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline">{order.areaLabel}</Badge>
                            <Badge
                                className={cn(
                                    'rounded-sm px-2.5 py-0.5 text-base font-medium',
                                    isDelivered
                                        ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                        : 'bg-primary text-primary-foreground border-transparent',
                                )}
                            >
                                {order.statusLabel ??
                                    driverOrderStatusLabel[order.status]}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {order.phone ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-12"
                                asChild
                            >
                                <a
                                    href={`tel:${order.phone}`}
                                    aria-label={`Call ${order.customerName}`}
                                >
                                    <Phone className="size-5" />
                                </a>
                            </Button>
                        ) : null}
                        <Button
                            type="button"
                            size="lg"
                            className="h-12"
                            asChild
                        >
                            <Link
                                href={orderChat.url(order.id)}
                                prefetch
                                data-test="driver-order-chat"
                            >
                                <MessageCircle data-icon="inline-start" />
                                Chat
                            </Link>
                        </Button>
                    </div>
                </div>

                <section className="bg-accent space-y-4 rounded-xl p-4 sm:p-5">
                    <div className="space-y-1">
                        <h2 className="text-foreground text-xl font-semibold">
                            Delivery details
                        </h2>
                        <p className="text-muted-foreground text-base leading-snug">
                            {order.addressSummary}
                        </p>
                        {order.unitLabel ? (
                            <p className="text-muted-foreground text-sm">
                                {order.unitLabel}
                            </p>
                        ) : null}
                        <p className="text-muted-foreground text-sm">
                            {order.windowLabel}
                            {order.etaLabel ? ` · ETA ${order.etaLabel}` : ''}
                            {` · ${paymentLabel}`}
                        </p>
                    </div>

                    <div className="text-muted-foreground space-y-1 text-base">
                        <p>{order.shippingAddress.line1}</p>
                        {order.shippingAddress.line2 ? (
                            <p>{order.shippingAddress.line2}</p>
                        ) : null}
                        <p>
                            {order.shippingAddress.city},{' '}
                            {order.shippingAddress.region}{' '}
                            {order.shippingAddress.postalCode}
                        </p>
                        <p>{order.shippingAddress.country}</p>
                    </div>

                    {order.notes ? (
                        <p className="text-foreground text-base leading-relaxed">
                            Note: {order.notes}
                        </p>
                    ) : null}
                </section>

                <div className="space-y-3">
                    <DriverRouteMapPanel
                        stops={[order]}
                        nextStop={order}
                        highlightId={order.id}
                        areaLabel={order.areaLabel}
                        navigateHref={mapsSearchUrl(
                            driverMapsQuery(order.addressSummary),
                        )}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        className="h-12 w-full"
                        asChild
                    >
                        <Link
                            href={routeIndex.url({
                                query: { highlight: order.id },
                            })}
                            prefetch
                            data-test="driver-order-map"
                        >
                            Show on route
                        </Link>
                    </Button>
                </div>

                <section className="border-border space-y-3 rounded-xl border p-4 sm:p-5">
                    <div className="flex items-center gap-2">
                        <Package className="text-foreground size-4" />
                        <h2 className="text-foreground text-xl font-semibold">
                            Products
                        </h2>
                    </div>

                    <ul className="flex flex-col">
                        {order.products.map((product, index) => (
                            <li key={`${product.name}-${index}`}>
                                {index > 0 ? (
                                    <Separator className="my-3" />
                                ) : null}
                                <div className="flex items-start justify-between gap-3 py-0.5">
                                    <div className="min-w-0 space-y-0.5">
                                        <p className="text-foreground font-medium">
                                            {product.name}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            Qty {product.quantityLabel}
                                        </p>
                                    </div>
                                    <p className="text-foreground shrink-0 text-base font-medium">
                                        {product.priceLabel}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <Separator />
                    <div className="flex items-center justify-between gap-3">
                        <p className="text-foreground font-semibold">Total</p>
                        <p className="text-foreground font-semibold">
                            {order.totalLabel}
                        </p>
                    </div>
                </section>

                <DriverDeliverOtpForm
                    orderId={order.id}
                    disabled={isDelivered}
                />

                <Button
                    variant="outline"
                    size="lg"
                    className="h-12 w-full"
                    asChild
                >
                    <Link href={ordersIndex.url()} prefetch>
                        Back to orders
                    </Link>
                </Button>
            </div>
        </>
    );
}

DriverOrderShow.layout = (props: DriverOrderShowProps) => ({
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
        {
            title: props.order.orderNumber,
            href: orderShow.url(props.order.id),
        },
    ],
});
