import { Head, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CircleDollarSign, Package, Settings2 } from 'lucide-react';
import { useMemo, type ReactNode } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import DiscountController from '@/actions/App/Http/Controllers/Platform/DiscountController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { CategoryFilterCombobox } from '@/components/catalog/category-filter-combobox';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { stockActionItems } from '@/lib/stock-query';

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category_name: string;
    image_url: string | null;
    quantity: number;
    selling_price: string | number | null;
    effective_price: string | number | null;
    has_discount: boolean;
    discount_type: string;
    discount_price: string | number | null;
};

type Branch = { id: number; name: string; slug: string };

type Summary = {
    products: number;
    missing_price: number;
};

type CategoryOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

function listingPrice(row: ProductRow): ReactNode {
    if (row.selling_price == null && row.effective_price == null) {
        return '—';
    }

    if (row.has_discount && row.selling_price != null) {
        return (
            <span className="tabular-nums">
                <s className="text-muted-foreground">{row.selling_price}</s>{' '}
                {row.effective_price ?? row.selling_price}
            </span>
        );
    }

    return (
        <span className="tabular-nums">
            {row.effective_price ?? row.selling_price}
        </span>
    );
}

export default function PlatformBranchProductsIndex({
    branch,
    summary,
    table: tablePayload,
}: {
    branch: Branch;
    summary: Summary;
    table: DataTablePayload<ProductRow>;
}) {
    const categories = (tablePayload.filterOptions?.categories ??
        []) as CategoryOption[];

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Products',
                href: BranchInventoryController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: BranchInventoryController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                category_id: tablePayload.filters.category_id ?? null,
                missing_price: tablePayload.filters.missing_price ?? null,
                status: tablePayload.filters.status ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<ProductRow>[]>(
        () => [
            {
                id: 'image',
                header: 'Image',
                cell: ({ row }) => (
                    <Avatar className="size-10 rounded-md">
                        <AvatarImage
                            src={row.original.image_url ?? undefined}
                            alt={row.original.name}
                        />
                        <AvatarFallback className="rounded-md">
                            {initials(row.original.name)}
                        </AvatarFallback>
                    </Avatar>
                ),
            },
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Product"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <DataTableTruncated className="font-medium">
                            {row.original.name}
                        </DataTableTruncated>
                        <DataTableTruncated className="text-muted-foreground text-xs">
                            {row.original.category_name || 'Uncategorized'}
                        </DataTableTruncated>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'sku',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="SKU"
                        sortable
                        sortKey="sku"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.sku ?? '—',
            },
            {
                accessorKey: 'quantity',
                header: 'Stock',
                cell: ({ row }) => (
                    <span className="tabular-nums">{row.original.quantity}</span>
                ),
            },
            {
                accessorKey: 'selling_price',
                header: 'Price',
                cell: ({ row }) => listingPrice(row.original),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'Manage',
                                icon: Settings2,
                                href: BranchInventoryController.show.url({
                                    branch: branch.slug,
                                    product: row.original.slug,
                                }),
                            },
                            ...stockActionItems(
                                {
                                    manage: StockController.manage.url(),
                                    transactions:
                                        StockController.transactions.url(),
                                    price: StockController.price.url(),
                                    discount: DiscountController.index.url(),
                                },
                                {
                                    productId: row.original.id,
                                    branchId: branch.id,
                                    locked: 'both',
                                },
                            ),
                        ]}
                    />
                ),
            },
        ],
        [branch.id, branch.slug, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title={`${branch.name} products`} />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title={`${branch.name} products`}
                    description="Stock and pricing for this branch"
                />

                <div className="grid gap-3 sm:grid-cols-2">
                    <DemoStatCard
                        icon={Package}
                        label="Catalog"
                        value={String(summary.products)}
                        hint="Products in master catalog"
                    />
                    <DemoStatCard
                        icon={CircleDollarSign}
                        label="Missing price"
                        value={String(summary.missing_price)}
                        hint="No active selling price"
                    />
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No products match this filter."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, slug, or SKU…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <CategoryFilterCombobox
                                        optionsUrl={CategoryController.options.url()}
                                        value={query.filters.category_id}
                                        selectedCategories={categories}
                                        onChange={(ids) =>
                                            query.setFilter('category_id', ids)
                                        }
                                    />
                                    <div className="grid gap-2">
                                        <Label>Price</Label>
                                        <Select
                                            value={String(
                                                query.filters.missing_price ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'missing_price',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Price" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All prices
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Missing price
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
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
