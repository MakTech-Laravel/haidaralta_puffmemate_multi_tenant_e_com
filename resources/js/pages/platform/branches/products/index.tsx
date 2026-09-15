import { Head, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    AlertTriangle,
    CircleDollarSign,
    Package,
    PackageCheck,
    Settings2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category_name: string;
    image_url: string | null;
    enabled: boolean;
    available_quantity: number;
    is_low_stock: boolean;
    selling_price: string | number | null;
};

type Branch = { id: number; name: string; slug: string };

type Summary = {
    products: number;
    enabled: number;
    low_stock: number;
    missing_price: number;
};

type CategoryOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
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
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);
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
                enabled: tablePayload.filters.enabled ?? null,
                low_stock: tablePayload.filters.low_stock ?? null,
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
                    <div>
                        <div className="font-medium">{row.original.name}</div>
                        <div className="text-muted-foreground text-xs">
                            {row.original.category_name || 'Uncategorized'}
                        </div>
                    </div>
                ),
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
                accessorKey: 'available_quantity',
                header: 'Stock',
                cell: ({ row }) => (
                    <div className="flex items-center gap-2">
                        {row.original.available_quantity}
                        {row.original.is_low_stock ? (
                            <Badge variant="destructive">Low</Badge>
                        ) : null}
                    </div>
                ),
            },
            {
                accessorKey: 'selling_price',
                header: 'Price',
                cell: ({ row }) => row.original.selling_price ?? '—',
            },
            {
                id: 'enabled',
                header: 'Enabled',
                cell: ({ row }) => (
                    <Checkbox
                        checked={row.original.enabled}
                        disabled={togglingSlug === row.original.slug}
                        onCheckedChange={(checked) => {
                            setTogglingSlug(row.original.slug);
                            router.put(
                                BranchInventoryController.toggle.url({
                                    branch: branch.slug,
                                    product: row.original.slug,
                                }),
                                { status: checked === true },
                                {
                                    preserveScroll: true,
                                    onFinish: () => setTogglingSlug(null),
                                },
                            );
                        }}
                    />
                ),
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
                        ]}
                    />
                ),
            },
        ],
        [branch.slug, query.sort, query.toggleSort, togglingSlug],
    );

    return (
        <>
            <Head title={`${branch.name} products`} />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title={`${branch.name} products`}
                    description="Enablement, stock, and pricing for this branch"
                />

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <DemoStatCard
                        icon={Package}
                        label="Catalog"
                        value={String(summary.products)}
                        hint="Products in master catalog"
                    />
                    <DemoStatCard
                        icon={PackageCheck}
                        label="Enabled"
                        value={String(summary.enabled)}
                        hint="Selling at this branch"
                    />
                    <DemoStatCard
                        icon={AlertTriangle}
                        label="Low stock"
                        value={String(summary.low_stock)}
                        hint="At or below minimum"
                        valueClassName={
                            summary.low_stock > 0
                                ? 'text-destructive'
                                : undefined
                        }
                    />
                    <DemoStatCard
                        icon={CircleDollarSign}
                        label="Missing price"
                        value={String(summary.missing_price)}
                        hint="Enabled without active price"
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
                                    <div className="grid gap-2">
                                        <Label>Category</Label>
                                        <Select
                                            value={String(
                                                query.filters.category_id ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'category_id',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[12rem]">
                                                <SelectValue placeholder="Category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All categories
                                                </SelectItem>
                                                {categories.map((category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={String(
                                                            category.id,
                                                        )}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Enabled</Label>
                                        <Select
                                            value={String(
                                                query.filters.enabled ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'enabled',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Enabled" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All enablement
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Enabled
                                                </SelectItem>
                                                <SelectItem value="0">
                                                    Disabled
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Stock</Label>
                                        <Select
                                            value={String(
                                                query.filters.low_stock ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'low_stock',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Stock" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All stock
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Low stock
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
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
