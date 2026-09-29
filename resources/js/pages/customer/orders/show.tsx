import { Form, Head } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Customer/OrderController';
import { OrderDeliveryTimeline } from '@/components/customer/order-delivery-timeline';
import { OrderTrackAddress } from '@/components/customer/order-track-address';
import { OrderTrackDriverCard } from '@/components/customer/order-track-driver';
import { OrderTrackMap } from '@/components/customer/order-track-map';
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

            <div className="bg-accent space-y-5 p-4 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-foreground mb-2 text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            Order Information {order.orderNumber}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Placed on {order.date} ·{' '}
                            {order.paymentStatusLabel ??
                                paymentStatusLabel[order.paymentStatus] ??
                                order.paymentStatus}
                        </p>
                    </div>
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
                </div>

                {order.handoverNotice ? (
                    <p className="bg-primary/10 text-foreground rounded-md p-3 text-sm">
                        {order.handoverNotice}
                    </p>
                ) : null}

                {order.canCancel ? (
                    <Form
                        {...OrderController.cancel.form(order.orderNumber)}
                        className="flex justify-end"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                            >
                                Cancel order
                            </Button>
                        )}
                    </Form>
                ) : null}

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                    <div className="order-2 flex min-w-0 flex-col gap-5 lg:order-1">
                        <OrderTrackProducts
                            products={order.products}
                            totalLabel={order.totalLabel}
                        />
                        <OrderTrackAddress address={order.shippingAddress} />
                        <OrderTrackMap />
                    </div>

                    <div className="order-1 flex flex-col gap-5 lg:order-2">
                        {order.driver ? (
                            <OrderTrackDriverCard driver={order.driver} />
                        ) : null}
                        <OrderDeliveryTimeline steps={order.timeline} />
                    </div>
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
