import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import {
    driverOrderStatusLabel,
    groupDriverAssignedOrdersByArea,
    type DriverAssignedOrder,
    type DriverOrderStatus,
} from '@/components/driver/driver-order-types';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as ordersIndex } from '@/routes/driver/orders';

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

    const filteredOrders =
        statusFilter === 'all'
            ? orders
            : orders.filter((order) => order.status === statusFilter);
    const areas = groupDriverAssignedOrdersByArea(filteredOrders);

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl md:text-3xl">
                        Assigned orders
                    </h1>
                    <p className="text-muted-foreground text-base">
                        Admins batch a neighbourhood to one driver. Work one
                        suburb at a time.
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
                                            {area.remainingCount} left ·{' '}
                                            {area.orderCount} assigned
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
