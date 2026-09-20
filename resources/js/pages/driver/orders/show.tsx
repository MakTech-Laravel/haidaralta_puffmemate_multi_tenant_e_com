import { Head, Link } from '@inertiajs/react';
import { MessageCircle, MapPin } from 'lucide-react';
import { OrderTrackMap } from '@/components/customer/order-track-map';
import { DriverDeliverOtpForm } from '@/components/driver/driver-deliver-otp-form';
import {
    driverOrderStatusLabel,
    type DriverOrderDetail,
} from '@/components/driver/driver-order-types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';

type DriverOrderShowProps = {
    order: DriverOrderDetail;
};

export default function DriverOrderShow({ order }: DriverOrderShowProps) {
    const isDelivered = order.status === 'delivered';

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
                    <Button
                        type="button"
                        size="lg"
                        className="h-12 shrink-0"
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

                <section className="bg-accent space-y-4 rounded-xl p-4 sm:p-5">
                    <div className="space-y-1">
                        <h2 className="text-foreground text-xl font-semibold">
                            Delivery details
                        </h2>
                        <p className="text-muted-foreground text-base leading-snug">
                            {order.addressSummary}
                        </p>
                        <p className="text-muted-foreground text-sm">
                            {order.windowLabel}
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

                <OrderTrackMap />

                <section className="border-border space-y-3 border p-4 sm:p-5">
                    <div className="flex items-center gap-2">
                        <MapPin className="text-foreground size-4" />
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
                    demoOtpHint={order.demoOtpHint}
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
