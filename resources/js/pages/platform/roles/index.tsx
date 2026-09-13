import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Download } from 'lucide-react';
import { useMemo, useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    buildDataTableQuery,
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    is_super_admin: boolean;
};

export default function PlatformRolesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<RoleRow>;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: RoleController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                kind: tablePayload.filters.kind ?? 'all',
                users: tablePayload.filters.users ?? 'all',
                permissions: tablePayload.filters.permissions ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const exportUrl = RoleController.export.url({
        query: buildDataTableQuery({
            filter: query.filters,
            sort: query.sort,
            per_page: query.perPage,
        }),
    });

    const columns = useMemo<ColumnDef<RoleRow>[]>(
        () => [
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
                accessorKey: 'permissions_count',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Permissions"
                        sortable
                        sortKey="permissions_count"
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
                        title="Users"
                        sortable
                        sortKey="users_count"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                id: 'actions',
                header: () => <span className="sr-only">Actions</span>,
                cell: ({ row }) => {
                    const role = row.original;
                    const items = [
                        {
                            label: 'View',
                            href: RoleController.show.url(role.id),
                        },
                        {
                            label: 'Edit',
                            href: RoleController.edit.url(role.id),
                        },
                        ...(!role.is_super_admin
                            ? [
                                  {
                                      label: 'Delete',
                                      destructive: true,
                                      separatorBefore: true,
                                      onSelect: () => setDeletingId(role.id),
                                  },
                              ]
                            : []),
                    ];

                    return <DataTableRowActions items={items} />;
                },
            },
        ],
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Manage platform roles and their permissions"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a href={exportUrl}>
                                <Download className="h-4 w-4" />
                                Export CSV
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={RoleController.create.url()}>
                                Create role
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No roles match the current filters."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search roles…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Type</Label>
                                        <Select
                                            value={String(
                                                query.filters.kind ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'kind',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Any type
                                                </SelectItem>
                                                <SelectItem value="system">
                                                    System
                                                </SelectItem>
                                                <SelectItem value="custom">
                                                    Custom
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Users</Label>
                                        <Select
                                            value={String(
                                                query.filters.users ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'users',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Users" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Any users
                                                </SelectItem>
                                                <SelectItem value="with">
                                                    With users
                                                </SelectItem>
                                                <SelectItem value="without">
                                                    Without users
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Permissions</Label>
                                        <Select
                                            value={String(
                                                query.filters.permissions ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'permissions',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Permissions" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Any permissions
                                                </SelectItem>
                                                <SelectItem value="none">
                                                    None
                                                </SelectItem>
                                                <SelectItem value="some">
                                                    Some
                                                </SelectItem>
                                                <SelectItem value="many">
                                                    Many
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
                open={deletingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingId(null);
                    }
                }}
                description="This will permanently delete the role. Roles with assigned users cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(RoleController.destroy.url(deletingId), {
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingId(null);
                        },
                    });
                }}
            />
        </>
    );
}

PlatformRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
    ],
};
