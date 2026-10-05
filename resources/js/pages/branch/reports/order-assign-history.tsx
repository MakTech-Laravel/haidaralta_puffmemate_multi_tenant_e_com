import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import OrderAssignHistoryController from '@/actions/App/Http/Controllers/Branch/Reports/OrderAssignHistoryController';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import { ExportDropdown } from '@/components/export/export-dropdown';
import Heading from '@/components/heading';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatAppDateTime } from '@/lib/datetime';

type HistoryRow = {
    id: number;
    fulfillment_id: number;
    fulfillment_number: string | null;
    fulfillment_url: string | null;
    from_driver_id: number | null;
    from_driver_name: string | null;
    from_driver_phone: string | null;
    from_driver_url: string | null;
    to_driver_id: number | null;
    to_driver_name: string | null;
    to_driver_phone: string | null;
    to_driver_url: string | null;
    changed_by_name: string | null;
    changed_by_url: string | null;
    reason: string | null;
    created_at: string | null;
};

type Branch = { id: number; name: string; slug: string };

type SelectedDriver = {
    id: number;
    name: string;
    phone?: string | null;
};

type SelectedFulfillment = {
    id: number;
    fulfillment_number: string;
};

function NameLink({
    href,
    children,
}: {
    href: string | null;
    children: string | null;
}) {
    if (!children) {
        return '—';
    }

    if (!href) {
        return <span className="block min-w-0 truncate">{children}</span>;
    }

    return (
        <Link
            href={href}
            className="text-foreground block min-w-0 truncate hover:underline"
        >
            {children}
        </Link>
    );
}

function DriverCell({
    name,
    phone,
    url,
}: {
    name: string | null;
    phone: string | null;
    url: string | null;
}) {
    if (!name) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <div className="min-w-0">
            <DataTableTruncated className="font-medium">
                <NameLink href={url}>{name}</NameLink>
            </DataTableTruncated>
            {phone ? (
                <DataTableTruncated className="text-muted-foreground text-xs">
                    {phone}
                </DataTableTruncated>
            ) : null}
        </div>
    );
}

export default function BranchOrderAssignHistory({
    branch,
    table: tablePayload,
    driverOptionsUrl,
}: {
    branch: Branch;
    table: DataTablePayload<HistoryRow> & {
        filterOptions?: {
            selected_driver?: SelectedDriver | null;
            selected_fulfillment?: SelectedFulfillment | null;
        };
    };
    driverOptionsUrl: string;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Reports',
                href: OrderAssignHistoryController.index.url(branch.slug),
            },
            {
                title: 'Order Assign History',
                href: OrderAssignHistoryController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: OrderAssignHistoryController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                order_id: tablePayload.filters.order_id ?? '',
                fulfillment_id: tablePayload.filters.fulfillment_id ?? 'all',
                driver_id: tablePayload.filters.driver_id ?? 'all',
                date: tablePayload.filters.date ?? '',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const [orderIdDraft, setOrderIdDraft] = useState(
        String(
            tablePayload.filters.order_id
            ?? tablePayload.filterOptions?.selected_fulfillment
                ?.fulfillment_number
            ?? '',
        ),
    );

    const columns = useMemo<ColumnDef<HistoryRow>[]>(
        () => [
            {
                id: 'order_id',
                header: 'Order ID',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <NameLink href={row.original.fulfillment_url}>
                        {row.original.fulfillment_number}
                    </NameLink>
                ),
            },
            {
                id: 'previous_driver',
                header: 'Previous driver',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <DriverCell
                        name={row.original.from_driver_name}
                        phone={row.original.from_driver_phone}
                        url={row.original.from_driver_url}
                    />
                ),
            },
            {
                id: 'assigned_driver',
                header: 'Assigned driver',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <DriverCell
                        name={row.original.to_driver_name}
                        phone={row.original.to_driver_phone}
                        url={row.original.to_driver_url}
                    />
                ),
            },
            {
                accessorKey: 'reason',
                header: 'Reason',
                cell: ({ row }) => row.original.reason ?? '—',
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Changed at"
                        sortable
                        sortKey="created_at"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => {
                    if (!row.original.created_at) {
                        return '—';
                    }

                    return (
                        formatAppDateTime(row.original.created_at) ||
                        row.original.created_at
                    );
                },
            },
            {
                accessorKey: 'changed_by_name',
                header: 'Changed by',
                cell: ({ row }) => (
                    <NameLink href={row.original.changed_by_url}>
                        {row.original.changed_by_name}
                    </NameLink>
                ),
            },
        ],
        [query.sort, query.toggleSort],
    );

    const selectedDriver = tablePayload.filterOptions?.selected_driver;
    const driverFilter = String(query.filters.driver_id ?? 'all');
    const selectedDriverLabel =
        selectedDriver !== undefined && selectedDriver !== null
            ? selectedDriver.phone
                ? `${selectedDriver.name} · ${selectedDriver.phone}`
                : selectedDriver.name
            : null;

    const applyOrderId = () => {
        const next = orderIdDraft.trim();
        query.setFilter('order_id', next === '' ? null : next);
    };

    return (
        <>
            <Head title="Order Assign History" />
            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Order Assign History"
                    description="Driver assign and reassign history for this branch"
                />
                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No driver assignment history found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search order ID, driver name, or phone…"
                            onClear={() => {
                                setOrderIdDraft('');
                                query.clearFilters();
                            }}
                            actions={
                                <ExportDropdown
                                    url={OrderAssignHistoryController.export.url(
                                        branch.slug,
                                    )}
                                    query={{
                                        filter: query.filters,
                                        sort: query.sort,
                                        per_page: query.perPage,
                                        page: tablePayload.meta.current_page,
                                    }}
                                    hasDateRange={false}
                                />
                            }
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Order ID</Label>
                                        <Input
                                            value={orderIdDraft}
                                            onChange={(event) =>
                                                setOrderIdDraft(
                                                    event.target.value,
                                                )
                                            }
                                            onBlur={applyOrderId}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    applyOrderId();
                                                }
                                            }}
                                            placeholder="Fulfillment number"
                                            className="w-44"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Driver</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={driverOptionsUrl}
                                            value={
                                                driverFilter === 'all'
                                                    ? null
                                                    : driverFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'driver_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={selectedDriverLabel}
                                            placeholder="All drivers"
                                            searchPlaceholder="Search by name or phone…"
                                            emptyText="No drivers found."
                                            noneLabel="All drivers"
                                            descriptionKey="sku"
                                            className="w-56"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Date</Label>
                                        <DatePicker
                                            value={
                                                (query.filters
                                                    .date as string) || null
                                            }
                                            onChange={(value) =>
                                                query.setFilter(
                                                    'date',
                                                    value || null,
                                                )
                                            }
                                            clearable
                                        />
                                    </div>
                                </>
                            }
                        />
                    }
                />
            </div>
        </>
    );
}
