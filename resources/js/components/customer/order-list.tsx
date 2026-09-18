import { Link, router } from '@inertiajs/react';
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
                    const detailsUrl = orderShow.url(order.id);

                    const openOrderDetails = () => {
                        router.visit(detailsUrl);
                    };

                    return (
                        <li key={order.id}>
                            <article
                                role="link"
                                tabIndex={0}
                                aria-label={`View order ${order.orderNumber}`}
                                className="bg-accent relative flex cursor-pointer flex-col gap-3 rounded-xl p-3 sm:flex-row sm:items-start sm:gap-5 sm:p-5"
                                onClick={openOrderDetails}
                                onKeyDown={(event) => {
                                    if (
                                        event.key === 'Enter' ||
                                        event.key === ' '
                                    ) {
                                        event.preventDefault();
                                        openOrderDetails();
                                    }
                                }}
                            >
                                <div className="bg-accent relative aspect-[2/1] w-full shrink-0 overflow-hidden rounded-sm sm:aspect-auto sm:size-36 sm:w-auto md:size-40">
                                    <div
                                        className="absolute inset-0 bg-cover bg-center"
                                        style={{
                                            backgroundImage: `url(${order.image})`,
                                        }}
                                        role="img"
                                        aria-label={order.branchName}
                                    />
                                </div>

                                <div className="flex min-w-0 flex-1 flex-col gap-3">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                            <p className="text-muted-foreground min-w-0 text-xs font-medium">
                                                Order ID: {order.orderNumber}
                                            </p>
                                            <span
                                                className={cn(
                                                    'inline-flex shrink-0 rounded-full px-2.5 py-1 text-sm font-normal',
                                                    order.status ===
                                                        'delivered'
                                                        ? 'bg-muted-foreground/15 text-foreground'
                                                        : 'bg-primary/10 text-primary',
                                                )}
                                            >
                                                {statusLabel[order.status]}
                                            </span>
                                        </div>
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
                                                <span className="bg-foreground text-background rounded-md px-2.5 py-1 text-sm font-medium">
                                                    More
                                                </span>
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
                                                onClick={(event) => {
                                                    event.preventDefault();
                                                    event.stopPropagation();
                                                    setCancelOrderId(order.id);
                                                }}
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
                                                    href={detailsUrl}
                                                    prefetch
                                                    onClick={(event) => {
                                                        event.stopPropagation();
                                                    }}
                                                >
                                                    {actionLabel[order.status]}
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                </div>
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
