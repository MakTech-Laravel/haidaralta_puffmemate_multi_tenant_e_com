import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Package, Pencil } from 'lucide-react';
import { useMemo, useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
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
import { categoryMerchandisingItems } from '@/components/platform/category-merchandising-actions';
import { CategoryNavSortSheet } from '@/components/platform/category-nav-sort-sheet';
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
    type ColorPickerValue,
} from '@/lib/color-picker';

type Branch = { id: number; name: string; slug: string };

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
    nav_sort: number | null;
    products_count: number;
};

type ParentOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function BranchCategoriesIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<CategoryRow> & {
        filterOptions?: { parents?: ParentOption[] };
    };
}) {
    const { catalog } = usePage().props;
    const [navSortCategory, setNavSortCategory] =
        useState<CategoryRow | null>(null);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Categories',
                href: CategoryController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: CategoryController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
                featured: tablePayload.filters.featured ?? 'all',
                show_in_nav: tablePayload.filters.show_in_nav ?? 'all',
                parent_id: tablePayload.filters.parent_id ?? 'all',
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
            ...(catalog.show_parent_category
                ? [
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
                          cell: ({ row }) =>
                              row.original.parent_name ?? '—',
                      } satisfies ColumnDef<CategoryRow>,
                  ]
                : []),
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
                    <Badge
                        variant={
                            row.original.status ? 'default' : 'secondary'
                        }
                    >
                        {row.original.status ? 'Active' : 'Inactive'}
                    </Badge>
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
                    <div className="flex flex-wrap items-center gap-1.5">
                        <Badge
                            variant={
                                row.original.show_in_nav
                                    ? 'default'
                                    : 'secondary'
                            }
                        >
                            {row.original.show_in_nav ? 'In nav' : 'Hidden'}
                        </Badge>
                        {row.original.show_in_nav &&
                        row.original.nav_sort !== null ? (
                            <span className="text-muted-foreground text-xs">
                                #{row.original.nav_sort}
                            </span>
                        ) : null}
                    </div>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const category = row.original;
                    const merchandising = categoryMerchandisingItems(
                        category,
                        {
                            status: CategoryController.toggleStatus.url({
                                branch: branch.slug,
                                category: category.slug,
                            }),
                            featured: CategoryController.toggleFeatured.url({
                                branch: branch.slug,
                                category: category.slug,
                            }),
                            nav: CategoryController.toggleNav.url({
                                branch: branch.slug,
                                category: category.slug,
                            }),
                        },
                        () => setNavSortCategory(category),
                    );

                    return (
                        <DataTableRowActions
                            contentClassName="w-56"
                            groups={[
                                {
                                    label: 'Merchandising',
                                    items: merchandising,
                                },
                                {
                                    label: 'Catalog',
                                    items: [
                                        {
                                            label: 'View',
                                            icon: Eye,
                                            href: CategoryController.show.url({
                                                branch: branch.slug,
                                                category: category.slug,
                                            }),
                                        },
                                        {
                                            label: 'View products',
                                            icon: Package,
                                            href: ProductController.index.url(
                                                branch.slug,
                                                {
                                                    query: {
                                                        filter: {
                                                            category_id:
                                                                category.id,
                                                        },
                                                    },
                                                },
                                            ),
                                        },
                                        {
                                            label: 'Edit',
                                            icon: Pencil,
                                            href: CategoryController.edit.url({
                                                branch: branch.slug,
                                                category: category.slug,
                                            }),
                                        },
                                    ],
                                },
                            ]}
                        />
                    );
                },
            },
        ],
        [branch.slug, catalog.show_parent_category, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Categories" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Categories"
                        description="Edit global catalog categories for this branch"
                    />
                    {catalog.branch_can_create_categories ? (
                    <Button asChild>
                        <Link
                            href={CategoryController.create.url(branch.slug)}
                        >
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
                                    {catalog.show_parent_category ? (
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
                                    ) : null}
                                </>
                            }
                        />
                    }
                />
            </div>

            <CategoryNavSortSheet
                open={navSortCategory !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setNavSortCategory(null);
                    }
                }}
                category={navSortCategory}
                actionUrl={
                    navSortCategory
                        ? CategoryController.updateNavSort.url({
                              branch: branch.slug,
                              category: navSortCategory.slug,
                          })
                        : null
                }
            />
        </>
    );
}
