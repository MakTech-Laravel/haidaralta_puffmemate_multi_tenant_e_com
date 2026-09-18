import { Head } from '@inertiajs/react';
import { OrderDeliveryTimeline } from '@/components/customer/order-delivery-timeline';
import { OrderTrackAddress } from '@/components/customer/order-track-address';
import { OrderTrackDriverCard } from '@/components/customer/order-track-driver';
import { OrderTrackMap } from '@/components/customer/order-track-map';
import { OrderTrackProducts } from '@/components/customer/order-track-products';
import type { CustomerOrderDetail } from '@/components/customer/order-track-types';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { index as ordersIndex } from '@/routes/customer/orders';

const statusLabel: Record<CustomerOrderDetail['status'], string> = {
    shipped: 'Shipped',
    processing: 'Processing',
    delivered: 'Delivered',
};

type CustomerOrderShowProps = {
    order: CustomerOrderDetail;
};

export default function CustomerOrderShow({ order }: CustomerOrderShowProps) {
    return (
        <>
            <Head title={`Order ${order.orderNumber}`} />

            <div className="bg-accent space-y-5 p-4 sm:p-6">
                <div className="">
                    <h2 className="text-foreground mb-2 text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Order Information {order.orderNumber}
                    </h2>
                    <Badge
                        className={cn(
                            'rounded-sm px-2.5 py-0.5 text-sm font-medium',
                            order.status === 'delivered'
                                ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                : 'bg-primary text-primary-foreground border-transparent',
                        )}
                    >
                        {statusLabel[order.status]}
                    </Badge>
                </div>

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                    <div className="order-2 flex min-w-0 flex-col gap-5 lg:order-1">
                        <OrderTrackProducts
                            branchName={order.branchName}
                            products={order.products}
                            totalLabel={order.totalLabel}
                        />
                        <OrderTrackAddress address={order.shippingAddress} />
                        <OrderTrackMap />
                    </div>

                    <div className="order-1 flex flex-col gap-5 lg:order-2">
                        <OrderTrackDriverCard driver={order.driver} />
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
