import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermission } from '@/hooks/use-permissions';
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category_ids: number[];
    category_name: string;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
    variants_count: number;
    deleted_at: string | null;
};

type CategoryOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformProductsIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<ProductRow> & {
        filterOptions?: { categories?: CategoryOption[] };
    };
}) {
    const { can } = usePermission();
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: ProductController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
                category_id: tablePayload.filters.category_id ?? 'all',
                trashed: tablePayload.filters.trashed ?? 'active',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const categories = tablePayload.filterOptions?.categories ?? [];

    const columns = useMemo<ColumnDef<ProductRow>[]>(
        () => [
            {
                id: 'image',
                header: 'Image',
                cell: ({ row }) => (
                    <Avatar
                        className="size-10"
                        style={
                            isTransparentColorPickerValue(
                                row.original.color_code,
                            )
                                ? undefined
                                : colorPickerStyle(row.original.color_code)
                        }
                    >
                        <AvatarImage
                            src={row.original.image_url ?? undefined}
                            alt={row.original.name}
                        />
                        <AvatarFallback className="bg-transparent">
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
                        title="Name"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
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
                accessorKey: 'category_name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Categories"
                        sortable
                        sortKey="category_name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'variants_count',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Variants"
                        sortable
                        sortKey="variants_count"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'status',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Status"
                        sortable
                        sortKey="status"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-1">
                        <Badge
                            variant={
                                row.original.status ? 'default' : 'secondary'
                            }
                        >
                            {row.original.status ? 'Active' : 'Inactive'}
                        </Badge>
                        {row.original.deleted_at ? (
                            <Badge variant="destructive">Trashed</Badge>
                        ) : null}
                    </div>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const product = row.original;
                    const items = [
                        {
                            label: 'View',
                            icon: Eye,
                            href: ProductController.show.url(product.slug),
                        },
                        ...(can('manage-catalog') && !product.deleted_at
                            ? [
                                  {
                                      label: 'Edit',
                                      icon: Pencil,
                                      href: ProductController.edit.url(
                                          product.slug,
                                      ),
                                  },
                              ]
                            : []),
                        ...(can('manage-catalog') && product.deleted_at
                            ? [
                                  {
                                      label: 'Restore',
                                      icon: RotateCcw,
                                      onSelect: () => {
                                          router.post(
                                              ProductController.restore.url(
                                                  product.slug,
                                              ),
                                          );
                                      },
                                  },
                              ]
                            : []),
                        ...(can('manage-catalog') && !product.deleted_at
                            ? [
                                  {
                                      label: 'Delete',
                                      icon: Trash2,
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () =>
                                          setDeletingSlug(product.slug),
                                  },
                              ]
                            : []),
                    ];

                    return <DataTableRowActions items={items} />;
                },
            },
        ],
        [can, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Products" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description="Manage platform catalog products"
                    />
                    {can('manage-catalog') ? (
                        <Button asChild>
                            <Link href={ProductController.create.url()}>
                                Add product
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, slug, or SKU…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={String(
                                                query.filters.status ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'status',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All statuses
                                                </SelectItem>
                                                <SelectItem value="active">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="inactive">
                                                    Inactive
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
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
                                        <Label>Trash</Label>
                                        <Select
                                            value={String(
                                                query.filters.trashed ??
                                                    'active',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'trashed',
                                                    value === 'active'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Trash" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">
                                                    Active only
                                                </SelectItem>
                                                <SelectItem value="only">
                                                    Trashed only
                                                </SelectItem>
                                                <SelectItem value="with">
                                                    Include trashed
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

            <ConfirmDeleteDialog
                open={deletingSlug !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingSlug(null);
                    }
                }}
                title="Delete product?"
                description="This will soft-delete the product and its variants."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        ProductController.destroy.url(deletingSlug),
                        {
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingSlug(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
    ],
};
