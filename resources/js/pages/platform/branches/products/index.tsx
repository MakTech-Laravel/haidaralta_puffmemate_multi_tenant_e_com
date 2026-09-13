import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Settings2 } from 'lucide-react';
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
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category_name: string;
    enabled: boolean;
    available_quantity: number;
    is_low_stock: boolean;
    selling_price: string | number | null;
};

type Branch = { id: number; name: string; slug: string };

export default function PlatformBranchProductsIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<ProductRow>;
}) {
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

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
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<ProductRow>[]>(
        () => [
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
                            {row.original.sku ?? row.original.slug}
                        </div>
                    </div>
                ),
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

            <div className="space-y-6 p-2 md:p-4">
                <Heading
                    title={`${branch.name} products`}
                    description="Enablement, stock, and pricing for this branch"
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
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
