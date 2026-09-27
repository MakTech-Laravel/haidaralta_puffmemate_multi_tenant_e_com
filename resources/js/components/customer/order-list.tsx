import { Link } from '@inertiajs/react';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/customer/orders';

export type CustomerOrderStatus =
    | 'pending'
    | 'confirmed'
    | 'processing'
    | 'partially_delivered'
    | 'delivered'
    | 'cancelled';

export type CustomerPaymentStatus = 'unpaid' | 'paid' | 'refunded';

export type CustomerOrder = {
    orderNumber: string;
    products: string[];
    priceLabel: string;
    status: CustomerOrderStatus;
    paymentStatus: CustomerPaymentStatus;
    date: string;
};

const VISIBLE_PRODUCT_COUNT = 2;

const statusLabel: Record<CustomerOrderStatus, string> = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    processing: 'Processing',
    partially_delivered: 'Partially delivered',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
};

const activeStatuses: CustomerOrderStatus[] = [
    'pending',
    'confirmed',
    'processing',
    'partially_delivered',
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
    const showPagination = lastPage > 1;

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
                                            <p className="text-muted-foreground min-w-0 text-xs font-medium">
                                                Order ID: {order.orderNumber} ·{' '}
                                                {order.date}
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

            {showPagination ? (
                <Pagination>
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                href="#"
                                aria-disabled={currentPage <= 1}
                                className={cn(
                                    currentPage <= 1 &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (currentPage > 1) {
                                        onPageChange(currentPage - 1);
                                    }
                                }}
                            />
                        </PaginationItem>
                        {Array.from({ length: lastPage }, (_, index) => {
                            const pageNumber = index + 1;

                            return (
                                <PaginationItem key={pageNumber}>
                                    <PaginationLink
                                        href="#"
                                        isActive={pageNumber === currentPage}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            onPageChange(pageNumber);
                                        }}
                                    >
                                        {pageNumber}
                                    </PaginationLink>
                                </PaginationItem>
                            );
                        })}
                        <PaginationItem>
                            <PaginationNext
                                href="#"
                                aria-disabled={currentPage >= lastPage}
                                className={cn(
                                    currentPage >= lastPage &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (currentPage < lastPage) {
                                        onPageChange(currentPage + 1);
                                    }
                                }}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            ) : null}
        </div>
    );
}
