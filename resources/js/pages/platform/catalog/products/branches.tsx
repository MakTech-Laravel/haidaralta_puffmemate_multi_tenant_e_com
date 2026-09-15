import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    AlertTriangle,
    Building2,
    CircleDollarSign,
    PackageCheck,
    Settings2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
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
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';

type BranchRow = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    branch_active: boolean;
    enabled: boolean;
    quantity: number | null;
    available_quantity: number | null;
    minimum_stock: number | null;
    is_low_stock: boolean;
    selling_price: string | null;
    discount_type: 'none' | 'fixed' | 'percent';
    discount_price: string | null;
    manage_url: string;
};

type ProductSummary = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    category_name: string;
    status: boolean;
};

type Summary = {
    branches: number;
    enabled: number;
    low_stock: number;
    missing_price: number;
};

function formatPrice(row: BranchRow): string {
    if (row.selling_price === null) {
        return '—';
    }

    if (row.discount_type === 'percent' && row.discount_price) {
        return `${row.selling_price} · ${row.discount_price}% off`;
    }

    if (row.discount_type === 'fixed' && row.discount_price) {
        return `${row.selling_price} · sale ${row.discount_price}`;
    }

    return row.selling_price;
}

export default function PlatformProductBranches({
    product,
    summary,
    table: tablePayload,
}: {
    product: ProductSummary;
    summary: Summary;
    table: DataTablePayload<BranchRow>;
}) {
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

    const colorCode = normalizeCatalogColorPickerValue(product.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Products', href: ProductController.index.url() },
            {
                title: product.name,
                href: ProductController.show.url(product.slug),
            },
            {
                title: 'Branches',
                href: BranchInventoryController.forProduct.url(product.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: BranchInventoryController.forProduct.url(product.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                enabled: tablePayload.filters.enabled ?? null,
                low_stock: tablePayload.filters.low_stock ?? null,
                missing_price: tablePayload.filters.missing_price ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<BranchRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Branch"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="flex flex-col gap-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium">
                                {row.original.name}
                            </span>
                            {!row.original.branch_active ? (
                                <Badge variant="secondary">
                                    Branch inactive
                                </Badge>
                            ) : null}
                            {row.original.is_low_stock ? (
                                <Badge
                                    variant="outline"
                                    className="border-destructive/40 text-destructive"
                                >
                                    Low stock
                                </Badge>
                            ) : null}
                        </div>
                        <div className="text-muted-foreground text-xs">
                            {row.original.code
                                ? `${row.original.code} · `
                                : ''}
                            /{row.original.slug}
                        </div>
                    </div>
                ),
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
                                    branch: row.original.slug,
                                    product: product.slug,
                                }),
                                { status: checked === true },
                                {
                                    preserveScroll: true,
                                    onFinish: () => setTogglingSlug(null),
                                },
                            );
                        }}
                        aria-label={`Enable ${row.original.name}`}
                    />
                ),
            },
            {
                accessorKey: 'quantity',
                header: 'On hand',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.quantity ?? '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'available_quantity',
                header: 'Available',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.available_quantity ?? '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'selling_price',
                header: 'Price',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {formatPrice(row.original)}
                    </span>
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
                                href: row.original.manage_url,
                            },
                        ]}
                    />
                ),
            },
        ],
        [product.slug, query.sort, query.toggleSort, togglingSlug],
    );

    return (
        <>
            <Head title={`${product.name} · Branches`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar
                            className="size-14 rounded-xl border border-border/70"
                            style={
                                transparent
                                    ? undefined
                                    : colorPickerStyle(colorCode)
                            }
                        >
                            {product.image_url ? (
                                <AvatarImage
                                    src={product.image_url}
                                    alt={product.name}
                                    className="object-contain p-1"
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl bg-transparent text-base">
                                {product.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="[&_header]:mb-0">
                            <Heading
                                title="Manage branches"
                                description={`Inventory, pricing, and enablement for ${product.name}`}
                            />
                            <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-2 text-sm">
                                <span>
                                    {product.category_name || 'Uncategorized'}
                                </span>
                                {product.sku ? (
                                    <>
                                        <span aria-hidden>·</span>
                                        <span className="font-mono text-xs">
                                            {product.sku}
                                        </span>
                                    </>
                                ) : null}
                                <Badge
                                    variant={
                                        product.status ? 'default' : 'secondary'
                                    }
                                >
                                    {product.status ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={ProductController.show.url(product.slug)}
                            >
                                Product details
                            </Link>
                        </Button>
                        <Button variant="default" asChild>
                            <Link href={ProductController.index.url()}>
                                All products
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <DemoStatCard
                        icon={Building2}
                        label="Branches"
                        value={String(summary.branches)}
                        hint="Locations in the network"
                    />
                    <DemoStatCard
                        icon={PackageCheck}
                        label="Enabled"
                        value={String(summary.enabled)}
                        hint="Selling this product"
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
                        hint="No active branch price"
                    />
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No branches match this filter."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search branch name, slug, or code…"
                            onClear={query.clearFilters}
                            filters={
                                <>
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
