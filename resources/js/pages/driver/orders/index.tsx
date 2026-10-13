import { Head } from '@inertiajs/react';
import { Package } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import { DriverOrderSearchField } from '@/components/driver/driver-order-search-field';
import {
    driverOrderFilterCounts,
    driverOrderStatusFilterLabel,
    filterDriverAssignedOrders,
    searchDriverAssignedOrders,
    type DriverAssignedOrder,
    type DriverOrderStatusFilter,
} from '@/components/driver/driver-order-types';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { useDriverOrdersRealtime } from '@/hooks/use-driver-orders-realtime';
import { index as ordersIndex } from '@/routes/driver/orders';

const realtimeProps = ['orders'];

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
    const [search, setSearch] = useState('');
    useDriverOrdersRealtime(realtimeProps);
    const counts = driverOrderFilterCounts(orders);
    const filteredOrders = useMemo(() => {
        return searchDriverAssignedOrders(
            filterDriverAssignedOrders(orders, statusFilter),
            search,
        );
    }, [orders, search, statusFilter]);

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        setSearch(search.trim());
    };

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-5">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        Orders
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {counts.active} active · {counts.delivered} delivered
                    </p>
                </div>

                {status ? (
                    <p className="bg-primary/10 text-foreground rounded-md px-4 py-3 text-base font-medium">
                        {status}
                    </p>
                ) : null}

                <DriverOrderSearchField
                    value={search}
                    onChange={setSearch}
                    onSubmit={submitSearch}
                />

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
                    size="sm"
                    className="-mx-1 flex-nowrap justify-start overflow-x-auto px-1"
                    aria-label="Filter orders by status"
                >
                    {statusFilters.map((filter) => (
                        <ToggleGroupItem
                            key={filter}
                            value={filter}
                            className="h-9 shrink-0 px-2.5 text-sm"
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

                {orders.length === 0 ? (
                    <DriverEmptyState
                        icon={Package}
                        title="No assigned orders"
                        description="New drops for this shift will appear here."
                    />
                ) : filteredOrders.length === 0 ? (
                    <DriverEmptyState
                        icon={Package}
                        title={
                            search !== ''
                                ? 'No matching orders'
                                : 'Nothing in this filter'
                        }
                        description={
                            search !== ''
                                ? 'Try a name, order number, suburb, or address.'
                                : 'Try another status to see the rest of your run.'
                        }
                    />
                ) : (
                    <ul className="flex flex-col gap-2.5">
                        {filteredOrders.map((order) => (
                            <li key={order.id}>
                                <DriverOrderCard order={order} />
                            </li>
                        ))}
                    </ul>
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
