import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import {
    driverOrderStatusLabel,
    type DriverAssignedOrder,
    type DriverOrderStatus,
} from '@/components/driver/driver-order-types';
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { index as ordersIndex } from '@/routes/driver/orders';

const PAGE_SIZE = 4;

type StatusFilter = 'all' | DriverOrderStatus;

type DriverOrdersIndexProps = {
    orders: DriverAssignedOrder[];
    status?: string | null;
};

export default function DriverOrdersIndex({
    orders,
    status = null,
}: DriverOrdersIndexProps) {
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
    const [page, setPage] = useState(1);

    const filteredOrders =
        statusFilter === 'all'
            ? orders
            : orders.filter((order) => order.status === statusFilter);

    const totalPages = Math.max(1, Math.ceil(filteredOrders.length / PAGE_SIZE));
    const currentPage = Math.min(Math.max(page, 1), totalPages);
    const pageOrders = filteredOrders.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );
    const showPagination = filteredOrders.length > PAGE_SIZE;

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Assigned orders
                    </h1>
                    <p className="text-muted-foreground max-w-xl text-base leading-relaxed">
                        Assigned stops for this shift. Open an order to view
                        products and collect the customer OTP.
                    </p>
                </div>

                {status ? (
                    <p className="bg-primary/10 text-foreground rounded-md px-4 py-3 text-base font-medium">
                        {status}
                    </p>
                ) : null}

                <div className="grid w-full max-w-xs gap-2">
                    <Label htmlFor="driver-orders-status-filter">Status</Label>
                    <Select
                        value={statusFilter}
                        onValueChange={(value) => {
                            setStatusFilter(value as StatusFilter);
                            setPage(1);
                        }}
                    >
                        <SelectTrigger
                            id="driver-orders-status-filter"
                            className="w-full"
                        >
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All orders</SelectItem>
                            <SelectItem value="out_for_delivery">
                                {driverOrderStatusLabel.out_for_delivery}
                            </SelectItem>
                            <SelectItem value="arrived">
                                {driverOrderStatusLabel.arrived}
                            </SelectItem>
                            <SelectItem value="delivered">
                                {driverOrderStatusLabel.delivered}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {orders.length === 0 ? (
                    <p className="text-muted-foreground text-base">
                        No assigned orders right now.
                    </p>
                ) : filteredOrders.length === 0 ? (
                    <p className="text-muted-foreground text-base">
                        No orders with this status.
                    </p>
                ) : (
                    <>
                        <ul className="flex flex-col gap-4">
                            {pageOrders.map((order) => (
                                <li key={order.id}>
                                    <DriverOrderCard order={order} />
                                </li>
                            ))}
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
                                                    setPage(currentPage - 1);
                                                }
                                            }}
                                        />
                                    </PaginationItem>
                                    {Array.from(
                                        { length: totalPages },
                                        (_, index) => {
                                            const pageNumber = index + 1;

                                            return (
                                                <PaginationItem
                                                    key={pageNumber}
                                                >
                                                    <PaginationLink
                                                        href="#"
                                                        isActive={
                                                            pageNumber ===
                                                            currentPage
                                                        }
                                                        onClick={(event) => {
                                                            event.preventDefault();
                                                            setPage(pageNumber);
                                                        }}
                                                    >
                                                        {pageNumber}
                                                    </PaginationLink>
                                                </PaginationItem>
                                            );
                                        },
                                    )}
                                    <PaginationItem>
                                        <PaginationNext
                                            href="#"
                                            aria-disabled={
                                                currentPage >= totalPages
                                            }
                                            className={cn(
                                                currentPage >= totalPages &&
                                                    'pointer-events-none opacity-50',
                                            )}
                                            onClick={(event) => {
                                                event.preventDefault();
                                                if (
                                                    currentPage < totalPages
                                                ) {
                                                    setPage(currentPage + 1);
                                                }
                                            }}
                                        />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        ) : null}
                    </>
                )}
            </div>
        </>
    );
}

DriverOrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
    ],
};
