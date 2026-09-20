import { Link } from '@inertiajs/react';
import {
    ChevronLeft,
    MapPin,
    Package,
    Phone,
} from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';
import { ConversationComposer } from '@/components/conversation/conversation-composer';
import { ConversationMessageRow } from '@/components/conversation/conversation-message-row';
import type {
    ConversationMessage,
    ReceiptState,
} from '@/components/conversation/types';
import {
    driverOrderStatusLabel,
    type DriverOrderDetail,
} from '@/components/driver/driver-order-types';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';

function sameDay(a: string | null, b: string | null): boolean {
    if (!a || !b) {
        return a === b;
    }

    return new Date(a).toDateString() === new Date(b).toDateString();
}

function dayLabel(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }

    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'long',
        year:
            date.getFullYear() === today.getFullYear() ? undefined : 'numeric',
    });
}

let nextClientId = 1;

type DriverOrderChatPanelProps = {
    order: DriverOrderDetail;
    messages: ConversationMessage[];
    viewerId: number;
    viewerName: string;
    canSend: boolean;
};

/**
 * Field messenger for one stop. Reuses conversation bubbles/composer; demo
 * sends stay local until real order conversations are wired for drivers.
 */
export function DriverOrderChatPanel({
    order,
    messages: initialMessages,
    viewerId,
    viewerName,
    canSend,
}: DriverOrderChatPanelProps) {
    const [messages, setMessages] = useState(initialMessages);
    const viewportRef = useRef<HTMLDivElement>(null);
    const isDelivered = order.status === 'delivered';
    const conversationId = initialMessages[0]?.conversation_id ?? 0;

    const scrollToBottom = useCallback(() => {
        const viewport = viewportRef.current;

        if (viewport) {
            viewport.scrollTop = viewport.scrollHeight;
        }
    }, []);

    const onSend = useCallback(
        (body: string, files: File[]): boolean => {
            if (!canSend || files.length > 0) {
                return false;
            }

            const clientId = -nextClientId++;

            setMessages((current) => [
                ...current,
                {
                    id: clientId,
                    conversation_id: conversationId,
                    type: 'text',
                    body,
                    created_at: new Date().toISOString(),
                    sender: {
                        id: viewerId,
                        name: viewerName,
                        avatar_url: null,
                    },
                    attachments: [],
                    pending: {
                        clientId,
                        state: 'sending',
                        body,
                        files: [],
                    },
                },
            ]);

            window.setTimeout(() => {
                setMessages((current) =>
                    current.map((one) =>
                        one.id === clientId
                            ? {
                                  ...one,
                                  id: Math.abs(clientId) + 10_000,
                                  pending: undefined,
                              }
                            : one,
                    ),
                );
            }, 450);

            requestAnimationFrame(scrollToBottom);

            return true;
        },
        [canSend, conversationId, scrollToBottom, viewerId, viewerName],
    );

    const discard = useCallback((clientId: number) => {
        setMessages((current) => current.filter((one) => one.id !== clientId));
    }, []);

    const rows = useMemo(
        () =>
            messages.map((message, index) => {
                const previous = messages[index - 1];
                const isOwn = message.sender?.id === viewerId;

                let receipt: ReceiptState | null = null;

                if (isOwn && message.type !== 'system') {
                    receipt = message.pending
                        ? message.pending.state
                        : 'sent';
                }

                return {
                    message,
                    isOwn,
                    receipt,
                    daySeparator:
                        previous === undefined ||
                        !sameDay(previous.created_at, message.created_at)
                            ? dayLabel(message.created_at)
                            : null,
                    showHeader:
                        previous === undefined ||
                        previous.sender?.id !== message.sender?.id ||
                        previous.type === 'system',
                };
            }),
        [messages, viewerId],
    );

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
                            <DemoDataBadge />
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

            <div
                ref={viewportRef}
                role="log"
                aria-live="polite"
                aria-label="Order chat transcript"
                className="min-h-0 flex-1 overflow-y-auto overscroll-contain"
            >
                <div className="flex min-h-full flex-col justify-end gap-1 px-3 py-4 sm:px-4">
                    <p className="text-muted-foreground pb-2 text-center text-xs">
                        Messages for this stop · customer and branch
                    </p>

                    {rows.map(
                        ({
                            message,
                            isOwn,
                            showHeader,
                            receipt,
                            daySeparator,
                        }) => (
                            <div key={message.id}>
                                {daySeparator ? (
                                    <div className="sticky top-0 z-10 flex justify-center py-2">
                                        <span className="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-medium">
                                            {daySeparator}
                                        </span>
                                    </div>
                                ) : null}
                                <ConversationMessageRow
                                    message={message}
                                    isOwn={isOwn}
                                    showHeader={showHeader}
                                    receipt={receipt}
                                    onDiscard={() => discard(message.id)}
                                />
                            </div>
                        ),
                    )}
                </div>
            </div>

            <ConversationComposer
                disabled={!canSend}
                onSend={onSend}
                onTyping={() => undefined}
            />
        </div>
    );
}
