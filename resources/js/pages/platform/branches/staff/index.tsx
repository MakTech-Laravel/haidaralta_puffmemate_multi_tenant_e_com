import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
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

type Branch = { id: number; name: string; slug: string; code: string };

type StaffRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    roles: string[];
};

export default function PlatformBranchScopedStaffIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<StaffRow>;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: BranchScopedStaffController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<StaffRow>[]>(
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
                accessorKey: 'phone',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Phone"
                        sortable
                        sortKey="phone"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                id: 'roles',
                header: 'Roles',
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-1">
                        {row.original.roles.map((role) => (
                            <Badge key={role} variant="secondary">
                                {role}
                            </Badge>
                        ))}
                    </div>
                ),
            },
            {
                accessorKey: 'is_active',
                header: 'Status',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.is_active ? 'default' : 'secondary'
                        }
                    >
                        {row.original.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: () => <span className="sr-only">Actions</span>,
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                href: BranchScopedStaffController.show.url([
                                    branch.slug,
                                    row.original.id,
                                ]),
                            },
                            {
                                label: 'Edit',
                                href: BranchScopedStaffController.edit.url([
                                    branch.slug,
                                    row.original.id,
                                ]),
                            },
                            {
                                label: 'Delete',
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () => setDeletingId(row.original.id),
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
            <Head title={`Staff · ${branch.name}`} />

            <div className="flex flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`${branch.name} staff`}
                        description={`Manage staff for ${branch.code}`}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={BranchController.show.url(branch.slug)}>
                                Back to branch
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={BranchScopedStaffController.create.url(
                                    branch.slug,
                                )}
                            >
                                Add staff
                            </Link>
                        </Button>
                    </div>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No staff found for this branch."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name or phone…"
                            onClear={query.clearFilters}
                            filters={
                                <div className="grid gap-2">
                                    <Label>Status</Label>
                                    <Select
                                        value={String(
                                            query.filters.is_active ?? 'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'is_active',
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
                open={deletingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingId(null);
                    }
                }}
                description="This will permanently delete the branch staff member."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        BranchScopedStaffController.destroy.url([
                            branch.slug,
                            deletingId,
                        ]),
                        {
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingId(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformBranchScopedStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
    ],
};
