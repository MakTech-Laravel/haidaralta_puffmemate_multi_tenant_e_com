import { Head, usePage } from '@inertiajs/react';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import type { ConversationMessage } from '@/components/conversation/types';
import { DriverOrderChatPanel } from '@/components/driver/driver-order-chat-panel';
import type { DriverOrderDetail } from '@/components/driver/driver-order-types';
import {
    chat as orderChat,
    index as ordersIndex,
    show as orderShow,
} from '@/routes/driver/orders';
import type { Auth } from '@/types';

type DriverOrderChatProps = {
    order: DriverOrderDetail;
    messages: ConversationMessage[];
    viewerId: number;
    canSend: boolean;
    channel: string | null;
};

export default function DriverOrderChat({
    order,
    messages,
    viewerId,
    canSend,
    channel,
}: DriverOrderChatProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const viewerName = auth.user?.name ?? 'You';

    return (
        <>
            <Head title={`Chat · ${order.orderNumber}`} />

            <DriverOrderChatPanel
                order={order}
                messages={messages}
                channel={channel}
                viewerId={viewerId}
                viewerName={viewerName}
                canSend={canSend}
                messageUrl={OrderController.storeMessage.url(order.id)}
            />
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
