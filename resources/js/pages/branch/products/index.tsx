import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

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

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

export default function BranchProductsIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<ProductRow>;
}) {
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

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
                    <div>
                        <div className="font-medium">{row.original.name}</div>
                        <div className="text-muted-foreground text-xs">
                            {row.original.category_name}
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
                                ProductController.toggle.url({
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
                header: () => <span className="sr-only">Actions</span>,
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'Manage',
                                href: ProductController.show.url({
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
            <Head title="Products" />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description={`Enable catalog products and manage stock for ${branch.name}`}
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
