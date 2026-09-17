import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { CancelOrderDialog } from '@/components/customer/cancel-order-dialog';
import { Button } from '@/components/ui/button';
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

export type CustomerOrderStatus = 'shipped' | 'processing' | 'delivered';

export type CustomerOrder = {
    id: string;
    orderNumber: string;
    branchName: string;
    products: string[];
    priceLabel: string;
    image: string;
    status: CustomerOrderStatus;
};

const PAGE_SIZE = 12;
const VISIBLE_PRODUCT_COUNT = 2;

const statusLabel: Record<CustomerOrderStatus, string> = {
    shipped: 'Shipped',
    processing: 'Processing',
    delivered: 'Delivered',
};

const actionLabel: Record<CustomerOrderStatus, string> = {
    shipped: 'Track Order',
    processing: 'Cancel Order',
    delivered: 'See Details',
};

type OrderListProps = {
    orders: CustomerOrder[];
    page: number;
    onPageChange: (page: number) => void;
    className?: string;
};

export function OrderList({
    orders,
    page,
    onPageChange,
    className,
}: OrderListProps) {
    const [cancelOrderId, setCancelOrderId] = useState<string | null>(null);
    const totalPages = Math.max(1, Math.ceil(orders.length / PAGE_SIZE));
    const currentPage = Math.min(Math.max(page, 1), totalPages);
    const pageOrders = orders.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );
    const showPagination = orders.length > PAGE_SIZE;
    const cancelOrder = orders.find((order) => order.id === cancelOrderId);

    return (
        <div className={cn('flex flex-col gap-6', className)}>
            <ul className="flex flex-col gap-4">
                {pageOrders.map((order) => {
                    const visibleProducts = order.products.slice(
                        0,
                        VISIBLE_PRODUCT_COUNT,
                    );
                    const hasMoreProducts =
                        order.products.length > VISIBLE_PRODUCT_COUNT;

                    return (
                        <li key={order.id}>
                            <article className="bg-accent relative flex flex-col gap-4 rounded-xl p-4 sm:flex-row sm:items-stretch sm:gap-5 sm:p-5">
                                <div className="bg-accent relative aspect-square w-full shrink-0 overflow-hidden rounded-sm sm:size-36 md:size-40">
                                    <div
                                        className="absolute inset-0 bg-cover bg-center"
                                        style={{
                                            backgroundImage: `url(${order.image})`,
                                        }}
                                        role="img"
                                        aria-label={order.branchName}
                                    />
                                </div>

                                <div className="flex min-w-0 flex-1 flex-col gap-3 pr-0 sm:pr-28">
                                    <div className="min-w-0 space-y-2">
                                        <p className="text-muted-foreground text-xs font-medium">
                                            Order ID: {order.orderNumber}
                                        </p>
                                        <h2 className="text-foreground text-lg font-semibold tracking-tight sm:text-xl">
                                            {order.branchName}
                                        </h2>
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
                                                <Link
                                                    href={orderShow.url(
                                                        order.id,
                                                    )}
                                                    prefetch
                                                    className="bg-foreground text-background hover:bg-foreground/90 rounded-md px-2.5 py-1 text-sm font-medium transition-colors"
                                                >
                                                    More
                                                </Link>
                                            ) : null}
                                        </div>
                                        <p className="text-foreground text-base font-bold">
                                            {order.priceLabel}
                                        </p>
                                    </div>

                                    <div>
                                        {order.status === 'processing' ? (
                                            <Button
                                                type="button"
                                                size="sm"
                                                className="bg-muted-foreground text-background hover:bg-muted-foreground/90 h-9 rounded-md px-4 text-sm font-semibold"
                                                onClick={() =>
                                                    setCancelOrderId(order.id)
                                                }
                                            >
                                                {actionLabel[order.status]}
                                            </Button>
                                        ) : (
                                            <Button
                                                type="button"
                                                size="sm"
                                                className={cn(
                                                    'h-9 rounded-md px-4 text-sm font-semibold',
                                                    order.status ===
                                                        'shipped' &&
                                                        'bg-primary text-primary-foreground hover:bg-primary/90',
                                                    order.status ===
                                                        'delivered' &&
                                                        'bg-foreground text-background hover:bg-foreground/90',
                                                )}
                                                asChild
                                            >
                                                <Link
                                                    href={orderShow.url(
                                                        order.id,
                                                    )}
                                                    prefetch
                                                >
                                                    {actionLabel[order.status]}
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <span
                                    className={cn(
                                        'absolute top-4 right-4 inline-flex rounded-full px-2.5 py-1 text-sm font-normal sm:top-5 sm:right-5',
                                        order.status === 'delivered'
                                            ? 'bg-muted-foreground/15 text-foreground'
                                            : 'bg-primary/10 text-primary',
                                    )}
                                >
                                    {statusLabel[order.status]}
                                </span>
                            </article>
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
                        {Array.from({ length: totalPages }, (_, index) => {
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
                                aria-disabled={currentPage >= totalPages}
                                className={cn(
                                    currentPage >= totalPages &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={(event) => {
                                    event.preventDefault();
                                    if (currentPage < totalPages) {
                                        onPageChange(currentPage + 1);
                                    }
                                }}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            ) : null}

            <CancelOrderDialog
                open={cancelOrderId !== null}
                orderNumber={cancelOrder?.orderNumber}
                onOpenChange={(open) => {
                    if (!open) {
                        setCancelOrderId(null);
                    }
                }}
            />
        </div>
    );
}
