import { Head, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye } from 'lucide-react';
import { useMemo } from 'react';
import OrderController from '@/actions/App/Http/Controllers/Branch/OrderController';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type FulfillmentRow = {
    id: number;
    fulfillment_number: string;
    order_id: number;
    order_number: string;
    customer_name: string;
    items_count: number;
    status: string;
    shipping_cost: number;
    is_paid: boolean;
    order_payment_status: string;
};

type Branch = { id: number; name: string; slug: string };

export default function BranchOrdersIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<FulfillmentRow>;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Orders', href: `/branch/${branch.slug}/orders` },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: OrderController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<FulfillmentRow>[]>(
        () => [
            {
                accessorKey: 'fulfillment_number',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Fulfillment #"
                        sortable
                        sortKey="fulfillment_number"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'order_number',
                header: 'Order #',
            },
            {
                accessorKey: 'customer_name',
                header: 'Customer',
            },
            {
                accessorKey: 'items_count',
                header: 'Items',
            },
            {
                accessorKey: 'status',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Status"
                        sortable
                        sortKey="status"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge variant="secondary" className="capitalize">
                        {row.original.status.replaceAll('_', ' ')}
                    </Badge>
                ),
            },
            {
                accessorKey: 'shipping_cost',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Shipping"
                        sortable
                        sortKey="shipping_cost"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) =>
                    `$${row.original.shipping_cost.toFixed(2)}`,
            },
            {
                id: 'payment',
                header: 'Payment',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.is_paid ? 'default' : 'secondary'
                        }
                    >
                        {row.original.is_paid ? 'Paid' : 'Unpaid'}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: OrderController.show.url([
                                    branch.slug,
                                    row.original.id,
                                ]),
                            },
                        ]}
                    />
                ),
            },
        ],
        [branch.slug, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Orders" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Orders"
                    description={`Fulfillments assigned to ${branch.name}`}
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No orders found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search fulfillment #…"
                            onClear={query.clearFilters}
                            filters={
                                <Select
                                    value={String(
                                        query.filters.status ?? 'all',
                                    )}
                                    onValueChange={(value) =>
                                        query.setFilter(
                                            'status',
                                            value === 'all' ? null : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-[10rem]">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All statuses
                                        </SelectItem>
                                        {(
                                            (tablePayload.filterOptions
                                                ?.status ?? []) as Array<{
                                                value: string;
                                                label: string;
                                            }>
                                        ).map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            }
                        />
                    }
                />
            </div>
        </>
    );
}
