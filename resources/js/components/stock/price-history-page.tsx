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

type ChangeRow = {
    field: string;
    label: string;
    from: string;
    to: string;
};

type HistoryRow = {
    id: number;
    branch_name: string | null;
    branch_url: string | null;
    product_name: string | null;
    product_sku: string | null;
    product_url: string | null;
    variant_name: string | null;
    source: string;
    source_label: string;
    changes: ChangeRow[];
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
    priceUpsert:
        'border-transparent bg-amber-500/10 text-amber-800 dark:bg-amber-500/15 dark:text-amber-400',
    stockIn:
        'border-transparent bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    productCreate:
        'border-transparent bg-sky-500/10 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
} as const;

function sourceBadgeClass(source: string): string {
    switch (source) {
        case 'price_upsert':
            return softBadge.priceUpsert;
        case 'stock_in':
            return softBadge.stockIn;
        case 'product_create':
            return softBadge.productCreate;
        default:
            return '';
    }
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

export function PriceHistoryPage({
    table: tablePayload,
    indexUrl,
    exportUrl,
    productOptionsUrl,
    branchOptionsUrl,
    hideBranchFilter = false,
}: {
    table: DataTablePayload<HistoryRow> & {
        filterOptions?: {
            selected_branch?: SelectedOption | null;
            selected_product?: SelectedOption | null;
            sources?: FilterOption[];
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
                source: tablePayload.filters.source ?? 'all',
                from: tablePayload.filters.from ?? '',
                to: tablePayload.filters.to ?? '',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<HistoryRow>[]>(
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
                        {row.original.variant_name ? (
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.variant_name}
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
                      } satisfies ColumnDef<HistoryRow>,
                  ]),
            {
                accessorKey: 'source_label',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Source"
                        sortable
                        sortKey="source"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant="outline"
                        className={cn(
                            'font-medium',
                            sourceBadgeClass(row.original.source),
                        )}
                    >
                        {row.original.source_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'changes',
                header: 'Changes',
                meta: { truncate: false },
                cell: ({ row }) =>
                    row.original.changes.length === 0 ? (
                        '—'
                    ) : (
                        <div className="flex min-w-0 flex-col gap-0.5">
                            {row.original.changes.map((change) => (
                                <DataTableTruncated
                                    key={change.field}
                                    className="tabular-nums"
                                >
                                    {change.label}: {change.from} → {change.to}
                                </DataTableTruncated>
                            ))}
                        </div>
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

    const sources = tablePayload.filterOptions?.sources ?? [];
    const selectedBranch = tablePayload.filterOptions?.selected_branch;
    const selectedProduct = tablePayload.filterOptions?.selected_product;
    const branchFilter = String(query.filters.branch_id ?? 'all');
    const productFilter = String(query.filters.product_id ?? 'all');

    return (
        <>
            <Head title="Price history" />
            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Price history"
                    description="Buying, regular, selling, and discount changes"
                />
                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No price history found."
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
                                    {hideBranchFilter ||
                                    !branchOptionsUrl ? null : (
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
                                        <Label>Source</Label>
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
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Source" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All sources
                                                </SelectItem>
                                                {sources.map((source) => (
                                                    <SelectItem
                                                        key={source.value}
                                                        value={String(
                                                            source.value,
                                                        )}
                                                    >
                                                        {source.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>From</Label>
                                        <DatePicker
                                            value={
                                                (query.filters
                                                    .from as string) || null
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
