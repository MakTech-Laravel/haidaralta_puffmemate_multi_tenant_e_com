import { Head, Link } from '@inertiajs/react';
import { ChevronRight, CircleCheck, History, Package } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import { DriverOrderSearchField } from '@/components/driver/driver-order-search-field';
import {
    driverOrderListTabLabel,
    driverOrderTabCounts,
    filterDriverAssignedOrders,
    searchDriverAssignedOrders,
    type DriverAssignedOrder,
    type DriverOrderListTab,
} from '@/components/driver/driver-order-types';
import { Button } from '@/components/ui/button';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { useDriverOrdersRealtime } from '@/hooks/use-driver-orders-realtime';
import { index as historyIndex } from '@/routes/driver/history';
import { index as ordersIndex } from '@/routes/driver/orders';

const realtimeProps = ['orders'];

const listTabs: DriverOrderListTab[] = ['active', 'delivered'];

type DriverOrdersIndexProps = {
    orders: DriverAssignedOrder[];
};

export default function DriverOrdersIndex({ orders }: DriverOrdersIndexProps) {
    const [tab, setTab] = useState<DriverOrderListTab>('active');
    const [search, setSearch] = useState('');
    useDriverOrdersRealtime(realtimeProps);
    const counts = driverOrderTabCounts(orders);
    const visibleOrders = useMemo(() => {
        return searchDriverAssignedOrders(
            filterDriverAssignedOrders(orders, tab),
            search,
        );
    }, [orders, search, tab]);

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        setSearch(search.trim());
    };

    return (
        <>
            <Head title="Orders" />

            <div className="flex flex-col gap-4">
                <div className="flex items-center justify-between gap-3">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        Orders
                    </h1>
                    <Button variant="outline" size="sm" className="h-9" asChild>
                        <Link
                            href={historyIndex.url()}
                            prefetch
                            data-test="driver-orders-history"
                        >
                            <History data-icon="inline-start" />
                            History
                        </Link>
                    </Button>
                </div>

                <ToggleGroup
                    type="single"
                    value={tab}
                    onValueChange={(value) => {
                        if (value === 'active' || value === 'delivered') {
                            setTab(value);
                        }
                    }}
                    variant="outline"
                    className="grid w-full grid-cols-2"
                    aria-label="Order list"
                >
                    {listTabs.map((item) => (
                        <ToggleGroupItem
                            key={item}
                            value={item}
                            className="data-[state=on]:bg-foreground data-[state=on]:text-background h-11 w-full gap-2 text-sm font-semibold"
                            data-test={`driver-orders-tab-${item}`}
                        >
                            {driverOrderListTabLabel[item]}
                            <span className="tabular-nums opacity-70">
                                {counts[item]}
                            </span>
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                <DriverOrderSearchField
                    value={search}
                    onChange={setSearch}
                    onSubmit={submitSearch}
                />

                {visibleOrders.length === 0 ? (
                    <DriverEmptyState
                        icon={
                            search === '' && tab === 'active'
                                ? CircleCheck
                                : Package
                        }
                        title={
                            search !== ''
                                ? 'No matching orders'
                                : tab === 'active'
                                  ? 'All stops done'
                                  : 'No deliveries yet today'
                        }
                        description={
                            search !== ''
                                ? 'Try a name, order number, suburb, address, or phone.'
                                : tab === 'active'
                                  ? 'New drops for this shift will appear here.'
                                  : 'Completed drops show here until the end of the day.'
                        }
                    />
                ) : (
                    <ul className="flex flex-col gap-2.5">
                        {visibleOrders.map((order) => (
                            <li key={order.id}>
                                <DriverOrderCard order={order} />
                            </li>
                        ))}
                    </ul>
                )}

                {tab === 'delivered' ? (
                    <Link
                        href={historyIndex.url()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground flex items-center justify-center gap-1 py-2 text-sm font-medium transition-colors"
                    >
                        See full order history
                        <ChevronRight className="size-4" />
                    </Link>
                ) : null}
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
