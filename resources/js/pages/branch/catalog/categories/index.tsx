import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil } from 'lucide-react';
import { useMemo } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
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
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: CategoryController.show.url({
                                    branch: branch.slug,
                                    category: row.original.slug,
                                }),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: CategoryController.edit.url({
                                    branch: branch.slug,
                                    category: row.original.slug,
                                }),
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
            <Head title="Categories" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Categories"
                        description="Create and edit global catalog categories for this branch"
                    />
                    <Button asChild>
                        <Link
                            href={CategoryController.create.url(branch.slug)}
                        >
                            Add category
                        </Link>
                    </Button>
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
                                </>
                            }
                        />
                    }
                />
            </div>
        </>
    );
}
