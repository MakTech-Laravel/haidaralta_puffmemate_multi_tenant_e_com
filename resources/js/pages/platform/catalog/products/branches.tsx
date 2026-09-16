import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Building2, CircleDollarSign, Settings2 } from 'lucide-react';
import { useMemo, type ReactNode } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { stockActionItems } from '@/lib/stock-query';

type BranchRow = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    branch_active: boolean;
    quantity: number;
    selling_price: string | number | null;
    effective_price: string | number | null;
    has_discount: boolean;
    discount_type: string;
    discount_price: string | number | null;
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
    missing_price: number;
};

function listingPrice(row: BranchRow): ReactNode {
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

export default function PlatformProductBranches({
    product,
    summary,
    table: tablePayload,
}: {
    product: ProductSummary;
    summary: Summary;
    table: DataTablePayload<BranchRow>;
}) {
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
                    <div className="flex min-w-0 flex-col gap-1">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <DataTableTruncated className="font-medium">
                                {row.original.name}
                            </DataTableTruncated>
                            {!row.original.branch_active ? (
                                <Badge variant="secondary">
                                    Branch inactive
                                </Badge>
                            ) : null}
                        </div>
                        <DataTableTruncated className="text-muted-foreground text-xs">
                            {row.original.code
                                ? `${row.original.code} · `
                                : ''}
                            /{row.original.slug}
                        </DataTableTruncated>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'quantity',
                header: 'Stock',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.quantity}
                    </span>
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
                                href: row.original.manage_url,
                            },
                            ...stockActionItems(
                                {
                                    manage: StockController.manage.url(),
                                    transactions:
                                        StockController.transactions.url(),
                                    price: StockController.price.url(),
                                    discount: StockController.discount.url(),
                                },
                                {
                                    productId: product.id,
                                    branchId: row.original.id,
                                    locked: 'both',
                                },
                            ),
                        ]}
                    />
                ),
            },
        ],
        [product.id, query.sort, query.toggleSort],
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
                                description={`Inventory and pricing for ${product.name}`}
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

                <div className="grid gap-3 sm:grid-cols-2">
                    <DemoStatCard
                        icon={Building2}
                        label="Branches"
                        value={String(summary.branches)}
                        hint="Locations in the network"
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
                                                value === 'all' ? null : value,
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
                            }
                        />
                    }
                />
            </div>
        </>
    );
}
