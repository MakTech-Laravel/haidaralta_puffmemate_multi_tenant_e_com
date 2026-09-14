import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
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

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    parent_name: string | null;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
    featured: boolean;
    show_in_nav: boolean;
    products_count: number;
    deleted_at: string | null;
};

type ParentOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformCategoriesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<CategoryRow> & {
        filterOptions?: { parents?: ParentOption[] };
    };
}) {
    const { can } = usePermission();
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: CategoryController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
                featured: tablePayload.filters.featured ?? 'all',
                show_in_nav: tablePayload.filters.show_in_nav ?? 'all',
                parent_id: tablePayload.filters.parent_id ?? 'all',
                trashed: tablePayload.filters.trashed ?? 'active',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const parents = tablePayload.filterOptions?.parents ?? [];

    const columns = useMemo<ColumnDef<CategoryRow>[]>(
        () => [
            {
                id: 'image',
                header: 'Image',
                cell: ({ row }) => (
                    <Avatar
                        className="size-10 rounded-md"
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
                        <AvatarFallback className="rounded-md bg-transparent">
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
                accessorKey: 'slug',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Slug"
                        sortable
                        sortKey="slug"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'parent_name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Parent"
                        sortable
                        sortKey="parent_name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.parent_name ?? '—',
            },
            {
                accessorKey: 'products_count',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Products"
                        sortable
                        sortKey="products_count"
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
                accessorKey: 'featured',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Featured"
                        sortable
                        sortKey="featured"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.featured ? 'default' : 'secondary'
                        }
                    >
                        {row.original.featured ? 'Featured' : 'Standard'}
                    </Badge>
                ),
            },
            {
                accessorKey: 'show_in_nav',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="In nav"
                        sortable
                        sortKey="show_in_nav"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.show_in_nav ? 'default' : 'secondary'
                        }
                    >
                        {row.original.show_in_nav ? 'In nav' : 'Hidden'}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const category = row.original;
                    const items = [
                        {
                            label: 'View',
                            icon: Eye,
                            href: CategoryController.show.url(category.slug),
                        },
                        ...(can('manage-catalog') && !category.deleted_at
                            ? [
                                  {
                                      label: 'Edit',
                                      icon: Pencil,
                                      href: CategoryController.edit.url(
                                          category.slug,
                                      ),
                                  },
                              ]
                            : []),
                        ...(can('manage-catalog') && category.deleted_at
                            ? [
                                  {
                                      label: 'Restore',
                                      icon: RotateCcw,
                                      onSelect: () => {
                                          router.post(
                                              CategoryController.restore.url(
                                                  category.slug,
                                              ),
                                          );
                                      },
                                  },
                              ]
                            : []),
                        ...(can('manage-catalog') && !category.deleted_at
                            ? [
                                  {
                                      label: 'Delete',
                                      icon: Trash2,
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () =>
                                          setDeletingSlug(category.slug),
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
            <Head title="Categories" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Categories"
                        description="Organize the platform product catalog"
                    />
                    {can('manage-catalog') ? (
                        <Button asChild>
                            <Link href={CategoryController.create.url()}>
                                Add category
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No categories found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name or slug…"
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
                                        <Label>Featured</Label>
                                        <Select
                                            value={String(
                                                query.filters.featured ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'featured',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Featured" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All featured
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Featured
                                                </SelectItem>
                                                <SelectItem value="0">
                                                    Standard
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>In nav</Label>
                                        <Select
                                            value={String(
                                                query.filters.show_in_nav ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'show_in_nav',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="In nav" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All nav
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    In nav
                                                </SelectItem>
                                                <SelectItem value="0">
                                                    Hidden
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Parent</Label>
                                        <Select
                                            value={String(
                                                query.filters.parent_id ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'parent_id',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[12rem]">
                                                <SelectValue placeholder="Parent" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All parents
                                                </SelectItem>
                                                {parents.map((parent) => (
                                                    <SelectItem
                                                        key={parent.id}
                                                        value={String(
                                                            parent.id,
                                                        )}
                                                    >
                                                        {parent.name}
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
                description="This will soft-delete the category. Categories with products cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        CategoryController.destroy.url(deletingSlug),
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

PlatformCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Categories', href: '/platform/categories' },
    ],
};
