import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import {
    driverOrderFilterCounts,
    driverOrderStatusFilterLabel,
    filterDriverAssignedOrders,
    groupDriverAssignedOrdersByArea,
    type DriverAssignedOrder,
    type DriverOrderStatusFilter,
} from '@/components/driver/driver-order-types';
import { DemoDataBadge } from '@/components/portal/demo-data-badge';
import { Badge } from '@/components/ui/badge';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { index as ordersIndex } from '@/routes/driver/orders';

const statusFilters: DriverOrderStatusFilter[] = [
    'active',
    'out_for_delivery',
    'arrived',
    'delivered',
    'all',
];

type DriverOrdersIndexProps = {
    orders: DriverAssignedOrder[];
    status?: string | null;
};

export default function DriverOrdersIndex({
    orders,
    status = null,
}: DriverOrdersIndexProps) {
    const [statusFilter, setStatusFilter] =
        useState<DriverOrderStatusFilter>('active');
    const counts = driverOrderFilterCounts(orders);
    const filteredOrders = filterDriverAssignedOrders(orders, statusFilter);
    const areas = groupDriverAssignedOrdersByArea(filteredOrders);

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                            Assigned orders
                        </h1>
                        <p className="text-muted-foreground text-base">
                            Admins batch a neighbourhood to one driver. Work one
                            suburb at a time.
                        </p>
                    </div>
                    <DemoDataBadge />
                </div>

                {status ? (
                    <p className="bg-primary/10 text-foreground rounded-md px-4 py-3 text-base font-medium">
                        {status}
                    </p>
                ) : null}

                <div className="space-y-2">
                    <p className="text-muted-foreground text-sm font-medium">
                        Status
                    </p>
                    <ToggleGroup
                        type="single"
                        value={statusFilter}
                        onValueChange={(value) => {
                            if (
                                value === 'all' ||
                                value === 'active' ||
                                value === 'out_for_delivery' ||
                                value === 'arrived' ||
                                value === 'delivered'
                            ) {
                                setStatusFilter(value);
                            }
                        }}
                        variant="outline"
                        size="lg"
                        className="-mx-1 flex-nowrap justify-start overflow-x-auto px-1"
                        aria-label="Filter orders by status"
                    >
                        {statusFilters.map((filter) => (
                            <ToggleGroupItem
                                key={filter}
                                value={filter}
                                className="h-11 shrink-0 px-3 text-sm"
                                aria-label={`${driverOrderStatusFilterLabel[filter]} orders`}
                                data-test={`driver-orders-filter-${filter}`}
                            >
                                {driverOrderStatusFilterLabel[filter]}
                                <span className="text-muted-foreground tabular-nums">
                                    {counts[filter]}
                                </span>
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
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
                    <div className="flex flex-col gap-8">
                        {areas.map((area) => (
                            <section
                                key={area.id}
                                className="flex flex-col gap-3"
                            >
                                <div className="flex flex-wrap items-end justify-between gap-2">
                                    <div className="space-y-0.5">
                                        <h2 className="text-foreground text-lg font-semibold">
                                            {area.label}
                                        </h2>
                                        <p className="text-muted-foreground text-sm">
                                            {statusFilter === 'all' ||
                                            statusFilter === 'active'
                                                ? `${area.remainingCount} left · ${area.orderCount} assigned`
                                                : `${area.orderCount} ${area.orderCount === 1 ? 'stop' : 'stops'}`}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {area.orderCount}{' '}
                                        {area.orderCount === 1
                                            ? 'drop'
                                            : 'drops'}
                                    </Badge>
                                </div>
                                <ul className="flex flex-col gap-4">
                                    {area.orders.map((order) => (
                                        <li key={order.id}>
                                            <DriverOrderCard order={order} />
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
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
