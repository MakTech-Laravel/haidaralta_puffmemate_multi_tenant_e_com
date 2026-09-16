import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Settings2 } from 'lucide-react';
import { useMemo, type ReactNode } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
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

export default function BranchProductsIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<ProductRow>;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Products',
                href: ProductController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: ProductController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
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
                            {row.original.category_name}
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
                        contentClassName="w-56"
                        groups={[
                            {
                                label: 'Catalog',
                                items: [
                                    {
                                        label: 'Manage',
                                        icon: Settings2,
                                        href: ProductController.show.url({
                                            branch: branch.slug,
                                            product: row.original.slug,
                                        }),
                                    },
                                ],
                            },
                            {
                                label: 'Stock',
                                items: stockActionItems(
                                    {
                                        manage: StockController.manage.url(
                                            branch.slug,
                                        ),
                                        transactions:
                                            StockController.transactions.url(
                                                branch.slug,
                                            ),
                                        price: StockController.price.url(
                                            branch.slug,
                                        ),
                                        discount: StockController.discount.url(
                                            branch.slug,
                                        ),
                                    },
                                    {
                                        productId: row.original.id,
                                        locked: 'both',
                                    },
                                ),
                            },
                        ]}
                    />
                ),
            },
        ],
        [branch.slug, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Products" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description={`Manage stock and pricing for ${branch.name}`}
                    />
                    <Button asChild>
                        <Link href={ProductController.create.url(branch.slug)}>
                            Add product
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No products found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search products…"
                            onClear={query.clearFilters}
                        />
                    }
                />
            </div>
        </>
    );
}
