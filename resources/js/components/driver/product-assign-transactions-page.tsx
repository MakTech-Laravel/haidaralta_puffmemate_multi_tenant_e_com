import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { formatAppDateTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';

type TransactionRow = {
    id: number;
    driver_id: number;
    driver_name: string | null;
    driver_phone: string | null;
    driver_avatar_url: string | null;
    driver_url: string | null;
    product_name: string | null;
    product_sku: string | null;
    product_image_url: string | null;
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

type SelectedProductOption = {
    id: number;
    name: string;
    sku?: string | null;
};

type SelectedDriverOption = {
    id: number;
    name: string;
    phone?: string | null;
};

type FilterOption = { value?: string; label?: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
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

export function ProductAssignTransactionsPage({
    table: tablePayload,
    indexUrl,
    exportUrl,
    productOptionsUrl,
    driverOptionsUrl,
}: {
    table: DataTablePayload<TransactionRow> & {
        filterOptions?: {
            selected_driver?: SelectedDriverOption | null;
            selected_product?: SelectedProductOption | null;
            types?: FilterOption[];
        };
    };
    indexUrl: string;
    exportUrl: string;
    productOptionsUrl: string;
    driverOptionsUrl: string;
}) {
    const query = useDataTableQuery({
        indexUrl,
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                driver_id: tablePayload.filters.driver_id ?? 'all',
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
                id: 'driver',
                header: 'Driver',
                meta: { truncate: false },
                cell: ({ row }) => {
                    const name = row.original.driver_name ?? '—';
                    const content = (
                        <>
                            <Avatar className="size-10 shrink-0">
                                <AvatarImage
                                    src={
                                        row.original.driver_avatar_url ??
                                        undefined
                                    }
                                    alt={name}
                                />
                                <AvatarFallback>
                                    {initials(name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0">
                                <DataTableTruncated className="font-medium">
                                    {name}
                                </DataTableTruncated>
                                {row.original.driver_phone ? (
                                    <DataTableTruncated className="text-muted-foreground text-xs">
                                        {row.original.driver_phone}
                                    </DataTableTruncated>
                                ) : null}
                            </div>
                        </>
                    );

                    if (!row.original.driver_url) {
                        return (
                            <div className="flex min-w-0 items-center gap-3">
                                {content}
                            </div>
                        );
                    }

                    return (
                        <Link
                            href={row.original.driver_url}
                            className="hover:text-primary flex min-w-0 items-center gap-3"
                        >
                            {content}
                        </Link>
                    );
                },
            },
            {
                id: 'product',
                header: 'Product',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="bg-muted relative size-10 shrink-0 overflow-hidden rounded-lg">
                            {row.original.product_image_url ? (
                                <img
                                    src={row.original.product_image_url}
                                    alt=""
                                    className="absolute inset-0 size-full object-cover"
                                />
                            ) : null}
                        </div>
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
                cell: ({ row }) => (
                    <Badge
                        variant="outline"
                        className={cn(
                            'border-transparent bg-sky-500/10 font-medium text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
                        )}
                    >
                        {row.original.type_label}
                    </Badge>
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
                    <Badge
                        variant="outline"
                        className="border-transparent bg-emerald-500/10 font-medium text-emerald-700 tabular-nums dark:bg-emerald-500/15 dark:text-emerald-400"
                    >
                        {row.original.quantity}
                    </Badge>
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

                    return (
                        formatAppDateTime(row.original.created_at) ||
                        row.original.created_at
                    );
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
        [query.sort, query.toggleSort],
    );

    const types = tablePayload.filterOptions?.types ?? [];
    const selectedDriver = tablePayload.filterOptions?.selected_driver;
    const selectedProduct = tablePayload.filterOptions?.selected_product;
    const driverFilter = String(query.filters.driver_id ?? 'all');
    const productFilter = String(query.filters.product_id ?? 'all');
    const selectedDriverLabel =
        selectedDriver !== undefined && selectedDriver !== null
            ? selectedDriver.phone
                ? `${selectedDriver.name} · ${selectedDriver.phone}`
                : selectedDriver.name
            : null;

    return (
        <>
            <Head title="Product assign transactions" />
            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Product assign transactions"
                    description="History of stock moved onto drivers from this branch"
                />
                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No product assign transactions found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search driver, product, or SKU…"
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
