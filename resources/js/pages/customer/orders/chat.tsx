import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Package } from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/Customer/OrderController';
import { OrderChatBody } from '@/components/conversation/order-chat-body';
import type { ConversationMessage } from '@/components/conversation/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/customer/orders';
import type { Auth } from '@/types';

type CustomerOrderChatProps = {
    order: {
        orderNumber: string;
        status: string;
        statusLabel: string;
        driverName: string | null;
    };
    messages: ConversationMessage[];
    viewerId: number;
    canSend: boolean;
    channel: string;
};

export default function CustomerOrderChat({
    order,
    messages,
    viewerId,
    canSend,
    channel,
}: CustomerOrderChatProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const viewerName = auth.user?.name ?? 'You';
    const isFinished =
        order.status === 'delivered' || order.status === 'cancelled';

    return (
        <>
            <Head title={`Chat · ${order.orderNumber}`} />

            <div className="bg-card flex min-h-[min(70svh,36rem)] flex-col overflow-hidden rounded-xl border xl:min-h-[min(75svh,40rem)]">
                <header className="border-border flex items-start gap-2 border-b px-3 py-3 sm:px-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="mt-0.5 shrink-0"
                        asChild
                    >
                        <Link
                            href={orderShow.url(order.orderNumber)}
                            prefetch
                            aria-label="Back to order"
                        >
                            <ChevronLeft />
                        </Link>
                    </Button>

                    <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-foreground truncate text-lg font-semibold tracking-tight sm:text-xl">
                                Chat with {order.driverName ?? 'your driver'}
                            </h1>
                            <Badge
                                className={cn(
                                    'rounded-sm px-2 py-0.5 text-sm font-medium',
                                    isFinished
                                        ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                        : 'bg-primary text-primary-foreground border-transparent',
                                )}
                            >
                                {order.statusLabel}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground inline-flex items-center gap-1 text-sm">
                            <Package className="size-3.5 shrink-0" aria-hidden />
                            {order.orderNumber}
                        </p>
                    </div>
                </header>

                <OrderChatBody
                    messages={messages}
                    channel={channel}
                    viewerId={viewerId}
                    viewerName={viewerName}
                    canSend={canSend}
                    messageUrl={OrderController.storeMessage.url(
                        order.orderNumber,
                    )}
                    intro="Messages about this delivery · driver and store"
                />
            </div>
        </>
    );
}

CustomerOrderChat.layout = (props: CustomerOrderChatProps) => ({
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
        {
            title: props.order.orderNumber,
            href: orderShow.url(props.order.orderNumber),
        },
        {
            title: 'Chat',
            href: orderChat.url(props.order.orderNumber),
        },
    ],
});
