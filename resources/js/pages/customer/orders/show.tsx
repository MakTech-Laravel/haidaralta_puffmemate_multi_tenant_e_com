import { Form, Head } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Customer/OrderController';
import { OrderDeliveryTimeline } from '@/components/customer/order-delivery-timeline';
import { OrderTrackAddress } from '@/components/customer/order-track-address';
import { OrderTrackDriverCard } from '@/components/customer/order-track-driver';
import { OrderTrackProducts } from '@/components/customer/order-track-products';
import type { CustomerOrderDetail } from '@/components/customer/order-track-types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index as ordersIndex } from '@/routes/customer/orders';

const statusLabel: Record<string, string> = {
    pending: 'Order Placed',
    processing: 'Preparing Your Order',
    ready: 'Ready For Delivery',
    assigned: 'Driver Assigned',
    picked_up: 'Order Picked Up',
    out_for_delivery: 'On The Way',
    delivery_failed: 'Delivery unsuccessful',
    returning: 'Returning your order',
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
                        </div>
                        <div className="flex flex-wrap items-center justify-end gap-2">
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

                    {order.handoverNotice ? (
                        <p className="bg-primary/10 text-foreground rounded-md p-3 text-sm">
                            {order.handoverNotice}
                        </p>
                    ) : null}
                </div>

                <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
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

                    <aside className="flex h-fit flex-col gap-5 lg:sticky lg:top-28">
                        {order.driver ? (
                            <OrderTrackDriverCard driver={order.driver} />
                        ) : null}
                        <OrderDeliveryTimeline steps={order.timeline} />
                    </aside>
                </div>
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
