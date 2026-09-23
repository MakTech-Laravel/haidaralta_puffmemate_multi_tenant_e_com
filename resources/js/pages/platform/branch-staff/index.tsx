import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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

type StaffRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    is_active: boolean;
    branch: { id: number; name: string; slug: string } | null;
    roles: string[];
};

type BranchOption = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformBranchStaffIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<StaffRow> & {
        filterOptions?: { branches?: BranchOption[] };
    };
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: BranchStaffController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const branches = tablePayload.filterOptions?.branches ?? [];

    const columns = useMemo<ColumnDef<StaffRow>[]>(
        () => [
            {
                id: 'avatar',
                header: 'Staff',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-10">
                            <AvatarImage
                                src={row.original.avatar_url ?? undefined}
                                alt={row.original.name}
                            />
                            <AvatarFallback>
                                {initials(row.original.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <DataTableTruncated className="font-medium">
                                {row.original.name}
                            </DataTableTruncated>
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.phone}
                            </DataTableTruncated>
                        </div>
                    </div>
                ),
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ row }) => row.original.email ?? '—',
            },
            {
                id: 'branch',
                header: 'Branch',
                cell: ({ row }) => row.original.branch?.name ?? '—',
            },
            {
                id: 'roles',
                header: 'Roles',
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-1">
                        {row.original.roles.map((role) => (
                            <Badge
                                key={role}
                                variant="secondary"
                                className="capitalize"
                            >
                                {role.replaceAll('-', ' ')}
                            </Badge>
                        ))}
                    </div>
                ),
            },
            {
                accessorKey: 'is_active',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Status"
                        sortable
                        sortKey="is_active"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
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
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: BranchStaffController.show.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: BranchStaffController.edit.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Delete',
                                icon: Trash2,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () => setDeletingId(row.original.id),
                            },
                        ]}
                    />
                ),
            },
        ],
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Branch staff" />

            <div className="flex flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Branch staff"
                        description="Manage staff across all branches from the platform"
                    />
                    <Button asChild>
                        <Link href={BranchStaffController.create.url()}>
                            Add staff
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No branch staff found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name or phone…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Branch</Label>
                                        <Select
                                            value={String(
                                                query.filters.branch_id ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'branch_id',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[12rem]">
                                                <SelectValue placeholder="Branch" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All branches
                                                </SelectItem>
                                                {branches.map((branch) => (
                                                    <SelectItem
                                                        key={branch.id}
                                                        value={String(
                                                            branch.id,
                                                        )}
                                                    >
                                                        {branch.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={String(
                                                query.filters.is_active ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'is_active',
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
                description="This will permanently delete the branch staff member."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        BranchStaffController.destroy.url(deletingId),
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

PlatformBranchStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
    ],
};
