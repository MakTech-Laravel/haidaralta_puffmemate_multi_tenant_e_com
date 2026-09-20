import { Head } from '@inertiajs/react';
import { Package } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import { DriverOrderSearchField } from '@/components/driver/driver-order-search-field';
import {
    groupDriverAssignedOrdersByCompletedLabel,
    searchDriverAssignedOrders,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { index as historyIndex } from '@/routes/driver/history';

type DriverOrderHistoryProps = {
    orders: DriverAssignedOrder[];
};

export default function DriverOrderHistory({
    orders,
}: DriverOrderHistoryProps) {
    const [search, setSearch] = useState('');
    const filteredOrders = useMemo(
        () => searchDriverAssignedOrders(orders, search),
        [orders, search],
    );
    const groups = groupDriverAssignedOrdersByCompletedLabel(filteredOrders);

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        setSearch(search.trim());
    };

    return (
        <>
            <Head title="Old orders" />

            <div className="flex flex-col gap-5">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        Old orders
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {orders.length} completed
                    </p>
                </div>

                <DriverOrderSearchField
                    value={search}
                    onChange={setSearch}
                    onSubmit={submitSearch}
                />

                {orders.length === 0 ? (
                    <DriverEmptyState
                        icon={Package}
                        title="No earlier orders"
                        description="Finished drops from previous shifts will show up here."
                    />
                ) : filteredOrders.length === 0 ? (
                    <DriverEmptyState
                        icon={Package}
                        title="No matching orders"
                        description="Try a name, order number, suburb, or address."
                    />
                ) : (
                    <div className="flex flex-col gap-6">
                        {groups.map((group) => (
                            <section
                                key={group.label}
                                className="flex flex-col gap-2.5"
                            >
                                <h2 className="text-muted-foreground text-sm font-medium tracking-wide uppercase">
                                    {group.label}
                                </h2>
                                <ul className="flex flex-col gap-2.5">
                                    {group.orders.map((order) => (
                                        <li key={order.id}>
                                            <DriverOrderCard
                                                order={order}
                                                showRoutePin={false}
                                            />
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

DriverOrderHistory.layout = {
    breadcrumbs: [
        {
            title: 'Old orders',
            href: historyIndex.url(),
        },
    ],
};
