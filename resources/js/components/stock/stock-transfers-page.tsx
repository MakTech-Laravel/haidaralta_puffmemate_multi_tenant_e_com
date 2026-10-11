import { router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Check, Package, PackageCheck, Truck } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { formatAppDateTime } from '@/lib/datetime';

export type StockTransferRow = {
    id: number;
    order_number: string | null;
    status: string;
    status_label: string;
    source_name: string;
    destination_name: string;
    estimated_cost_label: string;
    items_label: string;
    created_at: string | null;
    can_approve: boolean;
    can_pick: boolean;
    can_dispatch: boolean;
    can_receive: boolean;
};

type StockTransferAction = 'approve' | 'pick' | 'dispatch' | 'receive';

const statusBadgeClass: Record<string, string> = {
    requested:
        'border-transparent bg-amber-500/15 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
    approved:
        'border-transparent bg-sky-500/15 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300',
    picking:
        'border-transparent bg-indigo-500/15 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300',
    dispatched:
        'border-transparent bg-blue-500/15 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
    in_transit:
        'border-transparent bg-violet-500/15 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300',
    received:
        'border-transparent bg-emerald-500/15 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
    cancelled:
        'border-transparent bg-zinc-500/15 text-zinc-700 dark:bg-zinc-500/20 dark:text-zinc-300',
};

export function StockTransfersPage({
    table: tablePayload,
    indexUrl,
    description,
    actionUrl,
}: {
    table: DataTablePayload<StockTransferRow>;
    indexUrl: string;
    description: string;
    actionUrl: (id: number, action: StockTransferAction) => string;
}) {
    const [processing, setProcessing] = useState(false);
    const query = useDataTableQuery({
        indexUrl,
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });
    const statusOptions = (tablePayload.filterOptions?.status ?? []) as Array<{
        value: string;
        label: string;
    }>;

    const postAction = (id: number, action: StockTransferAction): void => {
        setProcessing(true);
        router.post(
            actionUrl(id, action),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const columns = useMemo<ColumnDef<StockTransferRow>[]>(
        () => [
            {
                accessorKey: 'order_number',
                header: 'Order #',
                cell: ({ row }) => row.original.order_number ?? '—',
            },
            {
                accessorKey: 'source_name',
                header: 'From',
            },
            {
                accessorKey: 'destination_name',
                header: 'To',
            },
            {
                accessorKey: 'items_label',
                header: 'Items',
                cell: ({ row }) => row.original.items_label || '—',
            },
            {
                accessorKey: 'estimated_cost_label',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Cost"
                        sortable
                        sortKey="estimated_cost"
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
                    <Badge
                        variant="outline"
                        className={statusBadgeClass[row.original.status]}
                    >
                        {row.original.status_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Created"
                        sortable
                        sortKey="created_at"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) =>
                    formatAppDateTime(row.original.created_at) || '—',
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const transfer = row.original;
                    const items = [
                        transfer.can_approve
                            ? {
                                  label: 'Approve',
                                  icon: Check,
                                  disabled: processing,
                                  onSelect: () =>
                                      postAction(transfer.id, 'approve'),
                              }
                            : null,
                        transfer.can_pick
                            ? {
                                  label: 'Pick',
                                  icon: Package,
                                  disabled: processing,
                                  onSelect: () => postAction(transfer.id, 'pick'),
                              }
                            : null,
                        transfer.can_dispatch
                            ? {
                                  label: 'Dispatch',
                                  icon: Truck,
                                  disabled: processing,
                                  onSelect: () =>
                                      postAction(transfer.id, 'dispatch'),
                              }
                            : null,
                        transfer.can_receive
                            ? {
                                  label: 'Receive',
                                  icon: PackageCheck,
                                  disabled: processing,
                                  onSelect: () =>
                                      postAction(transfer.id, 'receive'),
                              }
                            : null,
                    ].filter((item) => item !== null);

                    if (items.length === 0) {
                        return null;
                    }

                    return <DataTableRowActions items={items} />;
                },
            },
        ],
        [processing, query.sort, query.toggleSort],
    );

    return (
        <div className="space-y-4 p-2 md:p-4">
            <Heading title="Stock transfers" description={description} />
            <DataTable
                columns={columns}
                payload={tablePayload}
                perPage={query.perPage}
                isLoading={query.processing}
                onPerPageChange={query.changePerPage}
                emptyMessage="No stock transfers yet."
                toolbar={
                    <DataTableToolbar
                        search={query.search}
                        onSearchChange={query.setSearch}
                        searchPlaceholder="Order #, branch, or product"
                        onClear={query.clearFilters}
                        filters={
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Status
                                </span>
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
                                    <SelectTrigger className="w-44">
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
                            </div>
                        }
                    />
                }
            />
        </div>
    );
}
