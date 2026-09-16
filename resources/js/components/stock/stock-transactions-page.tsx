import { Head, Link } from '@inertiajs/react';
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
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
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
    branch_url: string | null;
    product_name: string | null;
    product_sku: string | null;
    product_url: string | null;
    type: string;
    type_label: string;
    quantity: number;
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
        return children;
    }

    return (
        <Link
            href={href}
            className="text-primary font-medium hover:underline"
        >
            {children}
        </Link>
    );
}

export function StockTransactionsPage({
    table: tablePayload,
    indexUrl,
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
                accessorKey: 'product_name',
                header: 'Product',
                cell: ({ row }) => (
                    <div>
                        <div>
                            <NameLink href={row.original.product_url}>
                                {row.original.product_name}
                            </NameLink>
                        </div>
                        {row.original.product_sku ? (
                            <div className="text-muted-foreground text-xs">
                                {row.original.product_sku}
                            </div>
                        ) : null}
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
                            searchPlaceholder="Search product, SKU, or branch…"
                            onClear={query.clearFilters}
                            filters={
                                <>
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
