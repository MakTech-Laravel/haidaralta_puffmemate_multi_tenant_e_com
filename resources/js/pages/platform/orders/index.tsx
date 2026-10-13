import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, UserRound } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
import OrderAssignController from '@/actions/App/Http/Controllers/Platform/OrderAssignController';
import OrderController from '@/actions/App/Http/Controllers/Platform/OrderController';
import { OrderAssignDriverSheet } from '@/components/branch/order-assign-driver-sheet';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { OrderListCustomer } from '@/components/orders/order-list-customer';
import { OrderPlacedDateFilters } from '@/components/orders/order-placed-date-filters';
import { OrderSourceBadge } from '@/components/orders/order-source-badge';
import { Badge } from '@/components/ui/badge';
import {
    orderStatusBadgeClass,
    paymentStatusBadgeClass,
} from '@/lib/order-badges';
import { formatAppDateTime } from '@/lib/datetime';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type OrderRow = {
    id: number;
    order_number: string;
    customer_id: number | null;
    customer_name: string;
    customer_phone: string | null;
    fulfillment_branch: string;
    branch_slug: string | null;
    fulfillment_id: number | null;
    fulfillment_number: string | null;
    total_label: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    source: string;
    source_label: string;
    created_at: string | null;
    assign_status: 'not_assign' | 'assign';
    driver_id: number | null;
    driver_name: string | null;
    can_assign: boolean;
};

export default function PlatformOrdersIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<OrderRow>;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Orders', href: '/platform/orders' },
        ],
    });

    const [assignOpen, setAssignOpen] = useState(false);
    const [assignOrder, setAssignOrder] = useState<{
        order_id: number;
        fulfillment_id: number | null;
        fulfillment_number: string | null;
        is_change: boolean;
    } | null>(null);

    const query = useDataTableQuery({
        indexUrl: OrderController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
                payment_status: tablePayload.filters.payment_status ?? 'all',
                source: tablePayload.filters.source ?? 'all',
                from: tablePayload.filters.from ?? null,
                to: tablePayload.filters.to ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const statusOptions = (tablePayload.filterOptions?.status ?? []) as Array<{
        value: string;
        label: string;
    }>;
    const paymentStatusOptions = (tablePayload.filterOptions?.payment_status ??
        []) as Array<{ value: string; label: string }>;
    const sourceOptions = (tablePayload.filterOptions?.source ?? []) as Array<{
        value: string;
        label: string;
    }>;

    const columns = useMemo<ColumnDef<OrderRow>[]>(
        () => [
            {
                accessorKey: 'order_number',
                meta: { label: 'Order #' },
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Order #"
                        sortable
                        sortKey="order_number"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'source_label',
                header: 'Source',
                meta: { label: 'Source' },
                cell: ({ row }) => (
                    <OrderSourceBadge
                        source={row.original.source}
                        label={row.original.source_label}
                    />
                ),
            },
            {
                accessorKey: 'customer_name',
                header: 'Customer',
                meta: { label: 'Customer', truncate: false },
                cell: ({ row }) => (
                    <OrderListCustomer
                        name={row.original.customer_name}
                        phone={row.original.customer_phone}
                        href={
                            row.original.customer_id
                                ? CustomerController.show.url(
                                      row.original.customer_id,
                                  )
                                : null
                        }
                    />
                ),
            },
            {
                accessorKey: 'fulfillment_branch',
                header: 'Branch',
                meta: { label: 'Branch', truncate: false },
                cell: ({ row }) =>
                    row.original.branch_slug ? (
                        <Link
                            href={BranchController.show.url(
                                row.original.branch_slug,
                            )}
                            className="hover:text-primary font-medium"
                        >
                            {row.original.fulfillment_branch}
                        </Link>
                    ) : (
                        row.original.fulfillment_branch
                    ),
            },
            {
                accessorKey: 'total_label',
                meta: { label: 'Total' },
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Total"
                        sortable
                        sortKey="total_amount"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'status',
                meta: { label: 'Status' },
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
                    <Badge
                        variant="outline"
                        className={orderStatusBadgeClass(row.original.status)}
                    >
                        {row.original.status_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'payment_status',
                header: 'Payment',
                meta: { label: 'Payment' },
                cell: ({ row }) => (
                    <Badge
                        variant="outline"
                        className={paymentStatusBadgeClass(
                            row.original.payment_status,
                        )}
                    >
                        {row.original.payment_status_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Placed"
                        sortable
                        sortKey="created_at"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                meta: { label: 'Placed' },
                cell: ({ row }) =>
                    formatAppDateTime(row.original.created_at) || '—',
            },
            {
                id: 'actions',
                header: 'Actions',
                enableHiding: false,
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: OrderController.show.url(row.original.id),
                            },
                            {
                                label:
                                    row.original.assign_status === 'assign'
                                        ? 'Change driver'
                                        : 'Assign driver',
                                icon: UserRound,
                                disabled: !row.original.can_assign,
                                onSelect: () => {
                                    setAssignOrder({
                                        order_id: row.original.id,
                                        fulfillment_id:
                                            row.original.fulfillment_id,
                                        fulfillment_number:
                                            row.original.fulfillment_number,
                                        is_change:
                                            row.original.assign_status ===
                                            'assign',
                                    });
                                    setAssignOpen(true);
                                },
                            },
                        ]}
                    />
                ),
            },
        ],
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Orders" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Orders"
                    description="Every order across every branch"
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No orders found."
                    columnVisibilityKey="platform-orders"
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Order #, customer, or branch"
                            layout="stacked"
                            onClear={query.clearFilters}
                            filters={
                                <div className="flex flex-wrap items-end gap-2">
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
                                            {statusOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select
                                        value={String(
                                            query.filters.payment_status ??
                                                'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'payment_status',
                                                value === 'all' ? null : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-[10rem]">
                                            <SelectValue placeholder="Payment" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All payments
                                            </SelectItem>
                                            {paymentStatusOptions.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <Select
                                        value={String(
                                            query.filters.source ?? 'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'source',
                                                value === 'all' ? null : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-[10rem]">
                                            <SelectValue placeholder="Source" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All sources
                                            </SelectItem>
                                            {sourceOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <OrderPlacedDateFilters
                                        from={
                                            typeof query.filters.from ===
                                            'string'
                                                ? query.filters.from
                                                : null
                                        }
                                        to={
                                            typeof query.filters.to === 'string'
                                                ? query.filters.to
                                                : null
                                        }
                                        onChange={(key, value) =>
                                            query.setFilter(key, value)
                                        }
                                    />
                                </div>
                            }
                        />
                    }
                />
            </div>

            <OrderAssignDriverSheet
                open={assignOpen}
                onOpenChange={(open) => {
                    setAssignOpen(open);

                    if (!open) {
                        setAssignOrder(null);
                    }
                }}
                fulfillmentId={assignOrder?.fulfillment_id ?? null}
                fulfillmentNumber={assignOrder?.fulfillment_number ?? null}
                mode={assignOrder?.is_change ? 'change' : 'assign'}
                candidatesUrl={
                    assignOrder
                        ? OrderAssignController.candidates.url(
                              assignOrder.order_id,
                          )
                        : null
                }
                storeUrl={
                    assignOrder
                        ? OrderAssignController.store.url(assignOrder.order_id)
                        : null
                }
            />
        </>
    );
}
