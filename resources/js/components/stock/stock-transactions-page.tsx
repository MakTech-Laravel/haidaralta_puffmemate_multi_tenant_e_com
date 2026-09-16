import { Head } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { format, isValid, parseISO } from 'date-fns';
import { useMemo } from 'react';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type TransactionRow = {
    id: number;
    branch_name: string | null;
    product_name: string | null;
    product_sku: string | null;
    type: string;
    type_label: string;
    quantity: number;
    before_quantity: number;
    after_quantity: number;
    created_by_name: string | null;
    created_at: string | null;
};

type FilterOption = { id?: number; value?: string; name?: string; label?: string; sku?: string | null };

export function StockTransactionsPage({
    table: tablePayload,
    indexUrl,
    hideBranchFilter = false,
}: {
    table: DataTablePayload<TransactionRow> & {
        filterOptions?: {
            branches?: FilterOption[];
            products?: FilterOption[];
            types?: FilterOption[];
        };
    };
    indexUrl: string;
    hideBranchFilter?: boolean;
}) {
    const query = useDataTableQuery({
        indexUrl,
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                product_id: tablePayload.filters.product_id ?? 'all',
                type: tablePayload.filters.type ?? 'all',
                from: tablePayload.filters.from ?? '',
                to: tablePayload.filters.to ?? '',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<TransactionRow>[]>(
        () => [
            ...(hideBranchFilter
                ? []
                : [
                      {
                          accessorKey: 'branch_name',
                          header: 'Branch',
                      } satisfies ColumnDef<TransactionRow>,
                  ]),
            {
                accessorKey: 'product_name',
                header: 'Product',
                cell: ({ row }) => (
                    <div>
                        <div className="font-medium">
                            {row.original.product_name ?? '—'}
                        </div>
                        <div className="text-muted-foreground text-xs">
                            {row.original.product_sku ?? ''}
                        </div>
                    </div>
                ),
            },
            {
                accessorKey: 'type_label',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Type"
                        sortable
                        sortKey="type"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'quantity',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Qty"
                        sortable
                        sortKey="quantity"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <span className="tabular-nums">{row.original.quantity}</span>
                ),
            },
            {
                accessorKey: 'before_quantity',
                header: 'Before',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.before_quantity}
                    </span>
                ),
            },
            {
                accessorKey: 'after_quantity',
                header: 'After',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.after_quantity}
                    </span>
                ),
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="When"
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

                    const parsed = parseISO(row.original.created_at);

                    return isValid(parsed)
                        ? format(parsed, 'dd MMM yyyy HH:mm')
                        : row.original.created_at;
                },
            },
            {
                accessorKey: 'created_by_name',
                header: 'By',
                cell: ({ row }) => row.original.created_by_name ?? '—',
            },
        ],
        [hideBranchFilter, query.sort, query.toggleSort],
    );

    const products = tablePayload.filterOptions?.products ?? [];
    const branches = tablePayload.filterOptions?.branches ?? [];
    const types = tablePayload.filterOptions?.types ?? [];

    return (
        <>
            <Head title="Stock transactions" />
            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Stock transactions"
                    description="Ledger of stock in, reduce, and adjustments"
                />
                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No stock transactions found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search product, SKU, or branch…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    {hideBranchFilter ? null : (
                                        <div className="grid gap-2">
                                            <Label>Branch</Label>
                                            <Select
                                                value={String(
                                                    query.filters.branch_id ??
                                                        'all',
                                                )}
                                                onValueChange={(value) =>
                                                    query.setFilter(
                                                        'branch_id',
                                                        value === 'all'
                                                            ? null
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-44">
                                                    <SelectValue placeholder="Branch" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All branches
                                                    </SelectItem>
                                                    {branches.map((branch) => (
                                                        <SelectItem
                                                            key={branch.id}
                                                            value={String(
                                                                branch.id,
                                                            )}
                                                        >
                                                            {branch.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}
                                    <div className="grid gap-2">
                                        <Label>Product</Label>
                                        <Select
                                            value={String(
                                                query.filters.product_id ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'product_id',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-52">
                                                <SelectValue placeholder="Product" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All products
                                                </SelectItem>
                                                {products.map((product) => (
                                                    <SelectItem
                                                        key={product.id}
                                                        value={String(
                                                            product.id,
                                                        )}
                                                    >
                                                        {product.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Type</Label>
                                        <Select
                                            value={String(
                                                query.filters.type ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'type',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All types
                                                </SelectItem>
                                                {types.map((type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={String(
                                                            type.value,
                                                        )}
                                                    >
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>From</Label>
                                        <DatePicker
                                            value={
                                                (query.filters.from as string) ||
                                                null
                                            }
                                            onChange={(value) =>
                                                query.setFilter(
                                                    'from',
                                                    value || null,
                                                )
                                            }
                                            clearable
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>To</Label>
                                        <DatePicker
                                            value={
                                                (query.filters.to as string) ||
                                                null
                                            }
                                            onChange={(value) =>
                                                query.setFilter(
                                                    'to',
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
