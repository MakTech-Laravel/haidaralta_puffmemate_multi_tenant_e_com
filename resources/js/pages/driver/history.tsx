import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { ChevronLeft, History } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import { DriverOrderCard } from '@/components/driver/driver-order-card';
import { DriverOrderSearchField } from '@/components/driver/driver-order-search-field';
import {
    groupDriverAssignedOrdersByCompletedLabel,
    type DriverAssignedOrder,
} from '@/components/driver/driver-order-types';
import { Spinner } from '@/components/ui/spinner';
import { index as historyIndex } from '@/routes/driver/history';
import { index as ordersIndex } from '@/routes/driver/orders';

const SEARCH_DEBOUNCE_MS = 300;

type DriverOrderHistoryProps = {
    orders: { data: DriverAssignedOrder[] };
    search: string;
};

function searchHistory(term: string): void {
    router.get(historyIndex.url(), term === '' ? {} : { search: term }, {
        only: ['orders', 'search'],
        reset: ['orders'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

export default function DriverOrderHistory({
    orders,
    search: appliedSearch,
}: DriverOrderHistoryProps) {
    const [search, setSearch] = useState(appliedSearch);
    const groups = groupDriverAssignedOrdersByCompletedLabel(orders.data);

    useEffect(() => {
        const term = search.trim();

        if (term === appliedSearch) {
            return;
        }

        const timer = window.setTimeout(
            () => searchHistory(term),
            SEARCH_DEBOUNCE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [search, appliedSearch]);

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        searchHistory(search.trim());
    };

    return (
        <>
            <Head title="Order history" />

            <div className="flex flex-col gap-4">
                <Link
                    href={ordersIndex.url()}
                    prefetch
                    className="text-muted-foreground hover:text-foreground -mb-1 inline-flex w-fit items-center gap-1 text-sm font-medium transition-colors xl:hidden"
                >
                    <ChevronLeft className="size-4" />
                    Orders
                </Link>

                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        Order history
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Delivered, returned, and cancelled drops.
                    </p>
                </div>

                <DriverOrderSearchField
                    value={search}
                    onChange={setSearch}
                    onSubmit={submitSearch}
                />

                {orders.data.length === 0 ? (
                    <DriverEmptyState
                        icon={History}
                        title={
                            appliedSearch !== ''
                                ? 'No matching orders'
                                : 'No finished orders yet'
                        }
                        description={
                            appliedSearch !== ''
                                ? 'Try a name, order number, suburb, address, or phone.'
                                : 'Finished drops will show up here.'
                        }
                    />
                ) : (
                    <InfiniteScroll
                        data="orders"
                        onlyNext
                        className="flex flex-col gap-6"
                        loading={
                            <div className="flex justify-center py-4">
                                <Spinner className="text-muted-foreground size-5" />
                            </div>
                        }
                    >
                        {groups.map((group) => (
                            <section
                                key={group.label}
                                className="flex flex-col gap-2.5"
                            >
                                <h2 className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
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
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}

DriverOrderHistory.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: ordersIndex.url(),
        },
        {
            title: 'History',
            href: historyIndex.url(),
        },
    ],
};
