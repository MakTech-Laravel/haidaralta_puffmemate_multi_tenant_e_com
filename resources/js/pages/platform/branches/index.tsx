import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
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

type BranchRow = {
    id: number;
    name: string;
    slug: string;
    code: string;
    logo: string | null;
    logo_url: string | null;
    status: boolean;
    users_count: number;
};

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

export default function PlatformBranchesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<BranchRow>;
}) {
    const { can } = usePermission();
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: BranchController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<BranchRow>[]>(
        () => [
            {
                id: 'logo',
                header: 'Logo',
                cell: ({ row }) => (
                    <Avatar className="size-10 rounded-md">
                        <AvatarImage
                            src={row.original.logo_url ?? undefined}
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
                        title="Name"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                accessorKey: 'code',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Code"
                        sortable
                        sortKey="code"
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
                accessorKey: 'users_count',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Staff"
                        sortable
                        sortKey="users_count"
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
                        variant={row.original.status ? 'default' : 'secondary'}
                    >
                        {row.original.status ? 'Active' : 'Inactive'}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const branch = row.original;
                    const items = [
                        {
                            label: 'View',
                            icon: Eye,
                            href: BranchController.show.url(branch.slug),
                        },
                        ...(can('edit-branches')
                            ? [
                                  {
                                      label: 'Edit',
                                      icon: Pencil,
                                      href: BranchController.edit.url(
                                          branch.slug,
                                      ),
                                  },
                              ]
                            : []),
                        ...(can('delete-branches')
                            ? [
                                  {
                                      label: 'Delete',
                                      icon: Trash2,
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () =>
                                          setDeletingSlug(branch.slug),
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
            <Head title="Branches" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Branches"
                        description="Manage store branches and their portals"
                    />
                    {can('create-branches') ? (
                        <Button asChild>
                            <Link href={BranchController.create.url()}>
                                Add branch
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No branches found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, code, or slug…"
                            onClear={query.clearFilters}
                            filters={
                                <div className="grid gap-2">
                                    <Label>Status</Label>
                                    <Select
                                        value={String(
                                            query.filters.status ?? 'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'status',
                                                value === 'all' ? null : value,
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
                description="This will permanently delete the branch. Branches with staff cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(BranchController.destroy.url(deletingSlug), {
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingSlug(null);
                        },
                    });
                }}
            />
        </>
    );
}

PlatformBranchesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
    ],
};
