import { Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { AccountListPagination } from '@/components/customer/account-list-pagination';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { shop } from '@/routes';
import { show as orderShow } from '@/routes/customer/orders';

export type CustomerOrderStatus =
    | 'pending'
    | 'processing'
    | 'ready'
    | 'assigned'
    | 'picked_up'
    | 'out_for_delivery'
    | 'delivery_failed'
    | 'returning'
    | 'delivered'
    | 'cancelled';

export type CustomerPaymentStatus =
    | 'pending_collection'
    | 'collected'
    | 'verified'
    | 'failed'
    | 'refunded';

export type CustomerOrder = {
    orderNumber: string;
    products: string[];
    priceLabel: string;
    status: CustomerOrderStatus;
    paymentStatus: CustomerPaymentStatus;
    source?: string;
    sourceLabel?: string;
    date: string;
};

const VISIBLE_PRODUCT_COUNT = 2;

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

const activeStatuses = [
    'pending',
    'processing',
    'ready',
    'assigned',
    'picked_up',
    'out_for_delivery',
    'delivery_failed',
    'returning',
];

type OrderListProps = {
    orders: CustomerOrder[];
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function OrderList({
    orders,
    currentPage,
    lastPage,
    onPageChange,
    className,
}: OrderListProps) {
    if (orders.length === 0) {
        return (
            <div
                className={cn(
                    'border-border bg-background flex flex-col items-center justify-center rounded-xl border px-6 py-16 text-center',
                    className,
                )}
            >
                <ClipboardList
                    className="text-muted-foreground mb-4 size-10"
                    strokeWidth={1.5}
                    aria-hidden
                />
                <h2 className="text-foreground text-lg font-semibold tracking-tight">
                    No orders yet
                </h2>
                <p className="text-muted-foreground mt-2 max-w-sm text-sm leading-relaxed">
                    When you place an order, it will show up here so you can
                    track delivery and review past purchases.
                </p>
                <Button asChild className="mt-6">
                    <Link href={shop.url()} prefetch>
                        Browse the shop
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className={cn('flex flex-col gap-6', className)}>
            <ul className="flex flex-col gap-4">
                {orders.map((order) => {
                    const visibleProducts = order.products.slice(
                        0,
                        VISIBLE_PRODUCT_COUNT,
                    );
                    const hasMoreProducts =
                        order.products.length > VISIBLE_PRODUCT_COUNT;
                    const detailsUrl = orderShow.url(order.orderNumber);
                    const isActive = activeStatuses.includes(order.status);

                    return (
                        <li key={order.orderNumber}>
                            <Link
                                href={detailsUrl}
                                prefetch
                                aria-label={`View order ${order.orderNumber}`}
                                className="bg-accent relative flex flex-col gap-3 rounded-xl p-3 sm:p-5"
                            >
                                <div className="flex min-w-0 flex-1 flex-col gap-3">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                            <p className="text-muted-foreground flex min-w-0 flex-wrap items-center gap-2 text-xs font-medium">
                                                <span>
                                                    Order ID:{' '}
                                                    {order.orderNumber} ·{' '}
                                                    {order.date}
                                                </span>
                                                {order.source === 'wholesale' &&
                                                order.sourceLabel ? (
                                                    <OrderSourceBadge
                                                        source={order.source}
                                                        label={
                                                            order.sourceLabel
                                                        }
                                                    />
                                                ) : null}
                                            </p>
                                            <span
                                                className={cn(
                                                    'inline-flex shrink-0 rounded-full px-2.5 py-1 text-sm font-normal',
                                                    isActive
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'bg-muted-foreground/15 text-foreground',
                                                )}
                                            >
                                                {statusLabel[order.status]}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {visibleProducts.map((product) => (
                                                <span
                                                    key={product}
                                                    className="bg-muted-foreground/15 text-muted-foreground rounded-md px-2.5 py-1 text-sm"
                                                >
                                                    {product}
                                                </span>
                                            ))}
                                            {hasMoreProducts ? (
                                                <span className="bg-foreground text-background rounded-md px-2.5 py-1 text-sm font-medium">
                                                    More
                                                </span>
                                            ) : null}
                                        </div>
                                        <p className="text-foreground text-base font-bold">
                                            {order.priceLabel}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </li>
                    );
                })}
            </ul>

            <AccountListPagination
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={onPageChange}
            />
        </div>
    );
}
