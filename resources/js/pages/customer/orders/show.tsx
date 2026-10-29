import { Form, Head, Link } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/Customer/OrderController';
import { OrderStatusProgress } from '@/components/orders/order-status-progress';
import { OrderReturnRequest } from '@/components/orders/order-return-request';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { OrderTrackAddress } from '@/components/customer/order-track-address';
import { OrderTrackDeliveryCode } from '@/components/customer/order-track-delivery-code';
import { OrderTrackDriverCard } from '@/components/customer/order-track-driver';
import { OrderTrackProducts } from '@/components/customer/order-track-products';
import type { CustomerOrderDetail } from '@/components/customer/order-track-types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    chat as orderChat,
    index as ordersIndex,
} from '@/routes/customer/orders';
import { show as wholesaleRequestShow } from '@/routes/customer/wholesale-requests';

const statusLabel: Record<string, string> = {
    pending: 'Awaiting confirmation',
    placed: 'Order placed',
    hold: 'On hold',
    processing: 'Preparing Your Order',
    ready: 'Ready For Delivery',
    assigned: 'Driver Assigned',
    picked_up: 'Order Picked Up',
    out_for_delivery: 'On The Way',
    arrived: 'Arrived',
    delivery_failed: 'Delivery unsuccessful',
    returning: 'Returning your order',
    returned: 'Returned to the branch',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

const paymentStatusLabel: Record<string, string> = {
    pending_collection: 'Payment due on delivery',
    collected: 'Payment received',
    verified: 'Payment received',
    failed: 'Payment unsuccessful',
    refunded: 'Refunded',
};

type CustomerOrderShowProps = {
    order: CustomerOrderDetail;
};

export default function CustomerOrderShow({ order }: CustomerOrderShowProps) {
    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="space-y-5">
                <div className="bg-accent space-y-4 p-4 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <h2 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                                Order {order.orderNumber}
                            </h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Placed on {order.date} ·{' '}
                                {order.paymentStatusLabel ??
                                    paymentStatusLabel[order.paymentStatus] ??
                                    order.paymentStatus}
                            </p>
                            {order.wholesaleRequestId ? (
                                <p className="text-muted-foreground mt-1 text-sm">
                                    From your{' '}
                                    <Link
                                        href={wholesaleRequestShow.url(
                                            order.wholesaleRequestId,
                                        )}
                                        prefetch
                                        className="text-primary font-medium hover:underline"
                                    >
                                        wholesale request #
                                        {order.wholesaleRequestId}
                                    </Link>
                                </p>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap items-center justify-end gap-2">
                            {order.source === 'wholesale' &&
                            order.sourceLabel ? (
                                <OrderSourceBadge
                                    source={order.source}
                                    label={order.sourceLabel}
                                    className="rounded-sm px-2.5 py-0.5 text-sm"
                                />
                            ) : null}
                            <Badge
                                className={cn(
                                    'rounded-sm px-2.5 py-0.5 text-sm font-medium',
                                    order.status === 'delivered' ||
                                        order.status === 'cancelled'
                                        ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                        : 'bg-primary text-primary-foreground border-transparent',
                                )}
                            >
                                {order.statusLabel ?? statusLabel[order.status]}
                            </Badge>
                            {order.canChat ? (
                                <Button size="sm" className="relative" asChild>
                                    <Link
                                        href={orderChat.url(order.orderNumber)}
                                        prefetch
                                    >
                                        <MessageCircle />
                                        Chat with driver
                                        {(order.chatUnreadCount ?? 0) > 0 ? (
                                            <span className="bg-primary-foreground text-primary ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-semibold tabular-nums">
                                                {(order.chatUnreadCount ?? 0) >
                                                99
                                                    ? '99+'
                                                    : order.chatUnreadCount}
                                            </span>
                                        ) : null}
                                    </Link>
                                </Button>
                            ) : null}
                            {order.canCancel ? (
                                <Form
                                    {...OrderController.cancel.form(
                                        order.orderNumber,
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            Cancel order
                                        </Button>
                                    )}
                                </Form>
                            ) : null}
                        </div>
                    </div>

                    {order.canResendCode ? (
                        <OrderTrackDeliveryCode
                            orderNumber={order.orderNumber}
                            code={order.deliveryCode ?? null}
                        />
                    ) : null}

                    <OrderReturnRequest
                        canRequest={order.canRequestReturn ?? false}
                        request={order.returnRequest ?? null}
                        form={OrderController.storeReturn.form(
                            order.orderNumber,
                        )}
                    />
                </div>

                <div
                    className={cn(
                        'grid items-start gap-5',
                        order.driver && 'lg:grid-cols-[minmax(0,1fr)_20rem]',
                    )}
                >
                    <div className="flex min-w-0 flex-col gap-5">
                        <OrderTrackProducts
                            products={order.products}
                            subtotalLabel={order.subtotalLabel}
                            discountLabel={order.discountLabel}
                            couponCode={order.couponCode}
                            deliveryFeeLabel={order.deliveryFeeLabel}
                            totalLabel={order.totalLabel}
                        />
                        <OrderTrackAddress address={order.shippingAddress} />
                    </div>

                    <aside className="flex h-fit flex-col gap-5">
                        {order.driver ? (
                            <OrderTrackDriverCard driver={order.driver} />
                        ) : null}
                    </aside>
                </div>

                <Card className="border-border/70 shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Order progress</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OrderStatusProgress
                            steps={order.timeline}
                            previousAttempts={order.timelinePrevious}
                            showActivity={false}
                            previousAttemptsLayout="sheet"
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CustomerOrderShow.layout = (props: CustomerOrderShowProps) => ({
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
        {
            title: props.order.orderNumber,
            href: '#',
        },
    ],
});
