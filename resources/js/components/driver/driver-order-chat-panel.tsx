import { Link } from '@inertiajs/react';
import { ChevronLeft, MapPin, Package, Phone } from 'lucide-react';
import { OrderChatBody } from '@/components/conversation/order-chat-body';
import type {
    ConversationMessage,
    ParticipantReceipt,
} from '@/components/conversation/types';
import {
    driverOrderStatusLabel,
    type DriverOrderDetail,
} from '@/components/driver/driver-order-types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';

type DriverOrderChatPanelProps = {
    order: DriverOrderDetail;
    messages: ConversationMessage[];
    channel: string | null;
    viewerId: number;
    viewerName: string;
    canSend: boolean;
    messageUrl: string;
    receipts?: ParticipantReceipt[];
    viewerLastReadMessageId?: number;
    deliveredUrl?: string | null;
    readUrl?: string | null;
};

/**
 * Field messenger for one stop. Sends land on the order conversation.
 */
export function DriverOrderChatPanel({
    order,
    messages,
    channel,
    viewerId,
    viewerName,
    canSend,
    messageUrl,
    receipts = [],
    viewerLastReadMessageId = 0,
    deliveredUrl = null,
    readUrl = null,
}: DriverOrderChatPanelProps) {
    const isDelivered = order.status === 'delivered';

    return (
        <div className="bg-card flex min-h-[min(70svh,36rem)] flex-col overflow-hidden rounded-xl border xl:min-h-[min(75svh,40rem)]">
            <header className="border-border flex flex-col gap-3 border-b px-3 py-3 sm:px-4">
                <div className="flex items-start gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="mt-0.5 shrink-0"
                        asChild
                    >
                        <Link
                            href={orderShow.url(order.id)}
                            prefetch
                            aria-label="Back to order"
                        >
                            <ChevronLeft />
                        </Link>
                    </Button>

                    <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-foreground truncate text-lg font-semibold tracking-tight sm:text-xl">
                                {order.customerName}
                            </h1>
                            <Badge variant="outline">{order.areaLabel}</Badge>
                            <Badge
                                className={cn(
                                    'rounded-sm px-2 py-0.5 text-sm font-medium',
                                    isDelivered
                                        ? 'bg-muted-foreground/15 text-foreground border-transparent'
                                        : 'bg-primary text-primary-foreground border-transparent',
                                )}
                            >
                                {order.statusLabel ??
                                    driverOrderStatusLabel[order.status]}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <span className="inline-flex items-center gap-1">
                                <Package className="size-3.5 shrink-0" aria-hidden />
                                {order.orderNumber}
                            </span>
                            <span className="inline-flex items-center gap-1">
                                <MapPin className="size-3.5 shrink-0" aria-hidden />
                                <span className="truncate">
                                    {order.addressSummary}
                                </span>
                            </span>
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-11 shrink-0"
                        asChild
                    >
                        <a
                            href={`tel:${order.phone}`}
                            aria-label={`Call ${order.customerName}`}
                        >
                            <Phone />
                        </a>
                    </Button>
                </div>
            </header>

            <OrderChatBody
                messages={messages}
                channel={channel}
                viewerId={viewerId}
                viewerName={viewerName}
                canSend={canSend}
                messageUrl={messageUrl}
                intro="Messages for this stop · customer and branch"
                receipts={receipts}
                viewerLastReadMessageId={viewerLastReadMessageId}
                deliveredUrl={deliveredUrl}
                readUrl={readUrl}
            />
        </div>
    );
}
