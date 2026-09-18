import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { format, isValid, parseISO } from 'date-fns';
import { useMemo } from 'react';
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
import { Badge } from '@/components/ui/badge';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type TransactionRow = {
    id: number;
    branch_name: string | null;
    branch_url: string | null;
    product_name: string | null;
    product_sku: string | null;
    product_url: string | null;
    type: string;
    type_label: string;
    reason_label: string | null;
    quantity: number;
    unit_cost: string | null;
    before_quantity: number;
    after_quantity: number;
    created_by_name: string | null;
    created_by_url: string | null;
    created_at: string | null;
};

type SelectedOption = {
    id: number;
    name: string;
    sku?: string | null;
};

type FilterOption = { value?: string; label?: string };

const softBadge = {
    stockIn:
        'border-transparent bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    stockOut:
        'border-transparent bg-rose-500/10 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
    adjustment:
        'border-transparent bg-amber-500/10 text-amber-800 dark:bg-amber-500/15 dark:text-amber-400',
} as const;

function typeBadgeClass(type: string): string {
    switch (type) {
        case 'stock_in':
            return softBadge.stockIn;
        case 'stock_out':
            return softBadge.stockOut;
        case 'adjustment':
            return softBadge.adjustment;
        default:
            return '';
    }
}

function quantityBadgeClass(type: string, quantity: number): string {
    if (type === 'stock_in') {
        return softBadge.stockIn;
    }

    if (type === 'stock_out') {
        return softBadge.stockOut;
    }

    if (type === 'adjustment') {
        return quantity < 0 ? softBadge.stockOut : softBadge.stockIn;
    }

    return softBadge.adjustment;
}

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

export function StockTransactionsPage({
    table: tablePayload,
    indexUrl,
    exportUrl,
    productOptionsUrl,
    branchOptionsUrl,
    hideBranchFilter = false,
}: {
    table: DataTablePayload<TransactionRow> & {
        filterOptions?: {
            selected_branch?: SelectedOption | null;
            selected_product?: SelectedOption | null;
            types?: FilterOption[];
        };
    };
    indexUrl: string;
    exportUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl?: string;
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
            {
                accessorKey: 'product_name',
                header: 'Product',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <DataTableTruncated>
                            <NameLink href={row.original.product_url}>
                                {row.original.product_name}
                            </NameLink>
                        </DataTableTruncated>
                        {row.original.product_sku ? (
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.product_sku}
                            </DataTableTruncated>
                        ) : null}
                    </div>
                ),
            },
            ...(hideBranchFilter
                ? []
                : [
                      {
                          accessorKey: 'branch_name',
                          header: 'Branch',
                          cell: ({ row }) => (
                              <NameLink href={row.original.branch_url}>
                                  {row.original.branch_name}
                              </NameLink>
                          ),
                      } satisfies ColumnDef<TransactionRow>,
                  ]),
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
                cell: ({ row }) => (
                    <Badge
                        variant="outline"
                        className={cn(
                            'font-medium',
                            typeBadgeClass(row.original.type),
                        )}
                    >
                        {row.original.type_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'reason_label',
                header: 'Reason',
                cell: ({ row }) => row.original.reason_label ?? '—',
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
                    <Badge
                        variant="outline"
                        className={cn(
                            'tabular-nums font-medium',
                            quantityBadgeClass(
                                row.original.type,
                                row.original.quantity,
                            ),
                        )}
                    >
                        {row.original.quantity}
                    </Badge>
                ),
            },
            {
                accessorKey: 'unit_cost',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Buying"
                        sortable
                        sortKey="unit_cost"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.unit_cost ?? '—'}
                    </span>
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
                cell: ({ row }) => (
                    <NameLink href={row.original.created_by_url}>
                        {row.original.created_by_name}
                    </NameLink>
                ),
            },
        ],
        [hideBranchFilter, query.sort, query.toggleSort],
    );

    const types = tablePayload.filterOptions?.types ?? [];
    const selectedBranch = tablePayload.filterOptions?.selected_branch;
    const selectedProduct = tablePayload.filterOptions?.selected_product;
    const branchFilter = String(query.filters.branch_id ?? 'all');
    const productFilter = String(query.filters.product_id ?? 'all');

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
                            searchPlaceholder={
                                hideBranchFilter
                                    ? 'Search product or SKU…'
                                    : 'Search product, SKU, or branch…'
                            }
                            onClear={query.clearFilters}
                            actions={
                                <ExportDropdown
                                    url={exportUrl}
                                    query={{
                                        filter: query.filters,
                                        sort: query.sort,
                                        per_page: query.perPage,
                                        page: tablePayload.meta.current_page,
                                    }}
                                    hasDateRange={Boolean(
                                        query.filters.from || query.filters.to,
                                    )}
                                />
                            }
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Product</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={productOptionsUrl}
                                            value={
                                                productFilter === 'all'
                                                    ? null
                                                    : productFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'product_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={
                                                selectedProduct?.name ?? null
                                            }
                                            placeholder="All products"
                                            searchPlaceholder="Search products…"
                                            emptyText="No product found."
                                            noneLabel="All products"
                                            className="w-56"
                                        />
                                    </div>
                                    {hideBranchFilter || !branchOptionsUrl ? null : (
                                        <div className="grid gap-2">
                                            <Label>Branch</Label>
                                            <InfiniteScrollCombobox
                                                optionsUrl={branchOptionsUrl}
                                                value={
                                                    branchFilter === 'all'
                                                        ? null
                                                        : branchFilter
                                                }
                                                onValueChange={(value) =>
                                                    query.setFilter(
                                                        'branch_id',
                                                        value,
                                                    )
                                                }
                                                selectedLabel={
                                                    selectedBranch?.name ?? null
                                                }
                                                placeholder="All branches"
                                                searchPlaceholder="Search branches…"
                                                emptyText="No branch found."
                                                noneLabel="All branches"
                                                descriptionKey={null}
                                                className="w-52"
                                            />
                                        </div>
                                    )}
                                    <div className="grid gap-2">
                                        <Label>Type</Label>
                                        <Select
                                            value={String(
                                                query.filters.type ??
                                                    'all',
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
