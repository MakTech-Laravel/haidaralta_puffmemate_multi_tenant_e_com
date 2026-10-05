import { Head, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, UserRound } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { Badge } from '@/components/ui/badge';
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
    customer_name: string;
    fulfillment_branch: string;
    fulfillment_id: number | null;
    fulfillment_number: string | null;
    total_label: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    source: string;
    source_label: string;
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
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const statusOptions = (tablePayload.filterOptions?.status ?? []) as Array<{
        value: string;
        label: string;
    }>;
    const paymentStatusOptions = (tablePayload.filterOptions
        ?.payment_status ?? []) as Array<{ value: string; label: string }>;
    const sourceOptions = (tablePayload.filterOptions?.source ?? []) as Array<{
        value: string;
        label: string;
    }>;

    const columns = useMemo<ColumnDef<OrderRow>[]>(
        () => [
            {
                accessorKey: 'order_number',
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
                cell: ({ row }) => (
                    <Badge variant="outline">{row.original.source_label}</Badge>
                ),
            },
            {
                accessorKey: 'customer_name',
                header: 'Customer',
            },
            {
                accessorKey: 'fulfillment_branch',
                header: 'Branch',
            },
            {
                accessorKey: 'total_label',
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
                    <Badge variant="secondary">{row.original.status_label}</Badge>
                ),
            },
            {
                accessorKey: 'payment_status',
                header: 'Payment',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.payment_status === 'collected' ||
                            row.original.payment_status === 'verified'
                                ? 'default'
                                : 'secondary'
                        }
                    >
                        {row.original.payment_status_label}
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
                                href: OrderController.show.url(
                                    row.original.id,
                                ),
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
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No orders found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search order #…"
                            onClear={query.clearFilters}
                            filters={
                                <div className="flex gap-2">
                                    <Select
                                        value={String(
                                            query.filters.status ?? 'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'status',
                                                value === 'all'
                                                    ? null
                                                    : value,
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
                                                value === 'all'
                                                    ? null
                                                    : value,
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
                                                value === 'all'
                                                    ? null
                                                    : value,
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
