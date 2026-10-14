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
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Spinner } from '@/components/ui/spinner';
import { index as historyIndex } from '@/routes/driver/history';
import { index as ordersIndex } from '@/routes/driver/orders';

const SEARCH_DEBOUNCE_MS = 300;

type HistoryFilters = {
    search: string;
    from: string;
    to: string;
};

type DriverOrderHistoryProps = {
    orders: { data: DriverAssignedOrder[] };
    total: number;
    filters: HistoryFilters;
    today: string;
};

function visitHistory(filters: HistoryFilters): void {
    const query: Record<string, string> = {
        from: filters.from,
        to: filters.to,
    };

    if (filters.search !== '') {
        query.search = filters.search;
    }

    router.get(historyIndex.url(), query, {
        only: ['orders', 'total', 'filters', 'today'],
        reset: ['orders'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

export default function DriverOrderHistory({
    orders,
    total,
    filters,
    today,
}: DriverOrderHistoryProps) {
    const [search, setSearch] = useState(filters.search);
    const groups = groupDriverAssignedOrdersByCompletedLabel(orders.data);
    const isSearching = filters.search !== '';

    useEffect(() => {
        const term = search.trim();

        if (term === filters.search) {
            return;
        }

        const timer = window.setTimeout(
            () => visitHistory({ ...filters, search: term }),
            SEARCH_DEBOUNCE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [search, filters]);

    const submitSearch = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        visitHistory({ ...filters, search: search.trim() });
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

                <DateRangePicker
                    value={{ from: filters.from, to: filters.to }}
                    max={today}
                    onChange={(range) => visitHistory({ ...filters, ...range })}
                />

                <DriverOrderSearchField
                    value={search}
                    onChange={setSearch}
                    onSubmit={submitSearch}
                />

                <p
                    className="text-muted-foreground text-sm"
                    data-test="driver-history-total"
                >
                    {total} {total === 1 ? 'order' : 'orders'}
                </p>

                {orders.data.length === 0 ? (
                    <DriverEmptyState
                        icon={History}
                        title={
                            isSearching
                                ? 'No matching orders'
                                : 'No finished orders'
                        }
                        description={
                            isSearching
                                ? 'Try a name, order number, suburb, address, or phone.'
                                : 'Nothing was delivered, returned, or cancelled in these dates. Try a wider range.'
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
