import { Head, Link } from '@inertiajs/react';
import {
    ChevronLeft,
    KeyRound,
    MapPin,
    MessageCircle,
    Navigation,
    Package,
    Phone,
} from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import { DriverDeliverOtpForm } from '@/components/driver/driver-deliver-otp-form';
import { DriverOrderNextAction } from '@/components/driver/driver-order-next-action';
import {
    driverMapsQuery,
    driverOrderStatusLabel,
    type DriverOrderDetail,
} from '@/components/driver/driver-order-types';
import { DriverOrderUnavailableSheet } from '@/components/driver/driver-order-unavailable-sheet';
import { OrderReturnRequest } from '@/components/orders/order-return-request';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { orderStatusBadgeClass } from '@/lib/order-badges';
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
        order.paymentMethod === 'cod' ? 'Cash on delivery' : 'Card / POS',
    );
    const collectsPayment = order.paymentMethod === 'cod' && !isDelivered;
    const stepAction =
        order.nextAction && order.nextAction.key !== 'complete'
            ? order.nextAction
            : null;
    const hasActionBar = Boolean(stepAction) || Boolean(order.canDeliver);
    const cityLine = [
        order.shippingAddress.city,
        order.shippingAddress.region,
        order.shippingAddress.postalCode,
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="flex flex-col gap-5">
                <Link
                    href={ordersIndex.url()}
                    prefetch
                    className="text-muted-foreground hover:text-foreground -mb-1 inline-flex w-fit items-center gap-1 text-sm font-medium transition-colors xl:hidden"
                >
                    <ChevronLeft className="size-4" />
                    Orders
                </Link>

                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1.5">
                        <p className="text-muted-foreground text-sm font-medium">
                            Order {order.orderNumber}
                        </p>
                        <h1 className="text-foreground truncate text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            {order.customerName}
                        </h1>
                        <Badge
                            className={cn(
                                'rounded-sm px-2 py-0.5 text-sm font-medium',
                                orderStatusBadgeClass(order.status),
                            )}
                            data-test="driver-order-status"
                        >
                            {order.statusLabel ??
                                driverOrderStatusLabel[order.status]}
                        </Badge>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {order.phone && !isDelivered ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-11"
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
                        {order.canChat ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-11"
                                asChild
                            >
                                <Link
                                    href={orderChat.url(order.id)}
                                    prefetch
                                    aria-label={`Chat about order ${order.orderNumber}`}
                                    data-test="driver-order-chat"
                                >
                                    <MessageCircle className="size-5" />
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <section className="bg-accent space-y-4 rounded-xl p-4 sm:p-5">
                    <div className="space-y-1">
                        <p className="text-foreground text-base font-semibold leading-snug">
                            {order.shippingAddress.line1 ||
                                order.addressSummary}
                        </p>
                        {order.unitLabel ? (
                            <p className="text-muted-foreground text-sm">
                                {order.unitLabel}
                            </p>
                        ) : null}
                        {cityLine !== '' ? (
                            <p className="text-muted-foreground text-sm">
                                {cityLine}
                            </p>
                        ) : null}
                        <p className="text-muted-foreground text-sm">
                            {order.windowLabel}
                            {order.etaLabel ? ` · ETA ${order.etaLabel}` : ''}
                        </p>
                    </div>

                    {order.notes ? (
                        <p className="bg-background text-foreground rounded-lg px-3 py-2 text-sm leading-relaxed">
                            <span className="font-semibold">Note: </span>
                            {order.notes}
                        </p>
                    ) : null}

                    <div className="grid grid-cols-2 gap-2">
                        <Button
                            type="button"
                            size="lg"
                            variant="outline"
                            className="bg-background h-11"
                            asChild
                        >
                            <a
                                href={mapsSearchUrl(
                                    driverMapsQuery(order.addressSummary),
                                )}
                                target="_blank"
                                rel="noreferrer"
                                data-test="driver-order-navigate"
                            >
                                <Navigation data-icon="inline-start" />
                                Navigate
                            </a>
                        </Button>
                        <Button
                            type="button"
                            size="lg"
                            variant="outline"
                            className="bg-background h-11"
                            asChild
                        >
                            <Link
                                href={routeIndex.url({
                                    query: { highlight: order.id },
                                })}
                                prefetch
                                data-test="driver-order-map"
                            >
                                <MapPin data-icon="inline-start" />
                                On route
                            </Link>
                        </Button>
                    </div>
                </section>

                <section className="border-border space-y-3 rounded-xl border p-4 sm:p-5">
                    <div className="flex items-center gap-2">
                        <Package className="text-foreground size-4" />
                        <h2 className="text-foreground text-lg font-semibold">
                            Products
                        </h2>
                        <span className="text-muted-foreground text-sm">
                            {order.productCount}
                        </span>
                    </div>

                    <ul className="flex flex-col">
                        {order.products.map((product, index) => (
                            <li key={`${product.name}-${index}`}>
                                {index > 0 ? (
                                    <Separator className="my-2.5" />
                                ) : null}
                                <div className="flex items-start justify-between gap-3">
                                    <p className="text-foreground min-w-0 text-sm font-medium">
                                        <span className="text-muted-foreground tabular-nums">
                                            {product.quantityLabel}×{' '}
                                        </span>
                                        {product.name}
                                    </p>
                                    <p className="text-foreground shrink-0 text-sm font-medium tabular-nums">
                                        {product.priceLabel}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <Separator />
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-foreground font-semibold">
                                {collectsPayment ? 'To collect' : 'Total'}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {paymentLabel}
                            </p>
                        </div>
                        <p className="text-foreground text-lg font-bold tabular-nums">
                            {order.totalLabel}
                        </p>
                    </div>
                </section>

                <OrderReturnRequest
                    canRequest={order.canRequestReturn ?? false}
                    request={order.returnRequest ?? null}
                    form={OrderController.storeReturn.form(order.id)}
                />

                {hasActionBar ? (
                    <>
                        <div aria-hidden className="h-20 xl:hidden" />
                        <div
                            className="border-border bg-background/95 fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 border-t px-4 py-3 backdrop-blur-sm xl:sticky xl:bottom-6 xl:rounded-xl xl:border xl:shadow-lg"
                            data-test="driver-order-action-bar"
                        >
                            <div className="mx-auto flex w-full max-w-5xl gap-2">
                                {stepAction ? (
                                    <div className="min-w-0 flex-1">
                                        <DriverOrderNextAction
                                            orderId={order.id}
                                            action={stepAction}
                                            className="h-12"
                                        />
                                    </div>
                                ) : null}
                                {order.canDeliver ? (
                                    <>
                                        {order.canFail ? (
                                            <div className="w-2/5 shrink-0">
                                                <DriverOrderUnavailableSheet
                                                    orderId={order.id}
                                                    reasons={
                                                        order.failureReasons ??
                                                        []
                                                    }
                                                />
                                            </div>
                                        ) : null}
                                        <div className="min-w-0 flex-1">
                                            <DriverDeliverOtpForm
                                                orderId={order.id}
                                                label={
                                                    collectsPayment
                                                        ? `Collect ${order.totalLabel}`
                                                        : 'Complete delivery'
                                                }
                                                icon={
                                                    <KeyRound data-icon="inline-start" />
                                                }
                                            />
                                        </div>
                                    </>
                                ) : null}
                            </div>
                        </div>
                    </>
                ) : null}
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
