import { Head, Link } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import type { DriverOrderDetail } from '@/components/driver/driver-order-types';
import { Button } from '@/components/ui/button';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';

type DriverOrderChatProps = {
    order: DriverOrderDetail;
};

export default function DriverOrderChat({ order }: DriverOrderChatProps) {
    return (
        <>
            <Head title={`Chat · ${order.orderNumber}`} />

            <div className="flex flex-col gap-6">
                <div className="space-y-2">
                    <p className="text-muted-foreground text-sm font-medium">
                        Order {order.orderNumber}
                    </p>
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Chat with {order.customerName}
                    </h1>
                    <p className="text-muted-foreground text-base leading-snug">
                        {order.addressSummary}
                    </p>
                </div>

                <DriverEmptyState
                    icon={MessageCircle}
                    title="Thread for this stop"
                    description="Messages with the customer and branch for this order will appear here."
                />

                <Button
                    variant="outline"
                    size="lg"
                    className="h-12 w-full"
                    asChild
                >
                    <Link href={orderShow.url(order.id)} prefetch>
                        Back to order
                    </Link>
                </Button>
            </div>
        </>
    );
}

DriverOrderChat.layout = (props: DriverOrderChatProps) => ({
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
        {
            title: props.order.orderNumber,
            href: orderShow.url(props.order.id),
        },
        {
            title: 'Chat',
            href: orderChat.url(props.order.id),
        },
    ],
});
