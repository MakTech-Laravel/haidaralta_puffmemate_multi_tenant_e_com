import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
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

type DriverRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    vehicle_type_label: string | null;
    availability_status_label: string | null;
    branches: { id: number; name: string; slug: string }[];
};

type BranchOption = { id: number; name: string; slug: string };

export default function PlatformDriversIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<DriverRow>;
}) {
    const [deactivatingId, setDeactivatingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);
    const branches = (tablePayload.filterOptions?.branches ??
        []) as BranchOption[];

    const query = useDataTableQuery({
        indexUrl: DriverController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                is_active: tablePayload.filters.is_active ?? null,
                availability_status:
                    tablePayload.filters.availability_status ?? null,
                branch_id: tablePayload.filters.branch_id ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<DriverRow>[]>(
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
                id: 'vehicle',
                header: 'Vehicle',
                cell: ({ row }) => row.original.vehicle_type_label ?? '—',
            },
            {
                id: 'availability',
                header: 'Availability',
                cell: ({ row }) =>
                    row.original.availability_status_label ?? '—',
            },
            {
                id: 'branches',
                header: 'Branches',
                cell: ({ row }) => (
                    <div className="flex flex-wrap gap-1">
                        {row.original.branches.length === 0 ? (
                            <span className="text-muted-foreground text-sm">
                                Unassigned
                            </span>
                        ) : (
                            row.original.branches.map((branch) => (
                                <Badge key={branch.id} variant="secondary">
                                    {branch.name}
                                </Badge>
                            ))
                        )}
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
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: DriverController.show.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: DriverController.edit.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Deactivate',
                                icon: Trash2,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () =>
                                    setDeactivatingId(row.original.id),
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
            <Head title="Drivers" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Drivers"
                        description="Platform-owned delivery drivers and branch assignments"
                    />
                    <Button asChild>
                        <Link href={DriverController.create.url()}>
                            Add driver
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No drivers match this filter."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, phone, or email…"
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
                                        <Label>Availability</Label>
                                        <Select
                                            value={String(
                                                query.filters
                                                    .availability_status ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'availability_status',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Availability" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All availability
                                                </SelectItem>
                                                <SelectItem value="available">
                                                    Available
                                                </SelectItem>
                                                <SelectItem value="busy">
                                                    Busy
                                                </SelectItem>
                                                <SelectItem value="offline">
                                                    Offline
                                                </SelectItem>
                                                <SelectItem value="on_break">
                                                    On break
                                                </SelectItem>
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
                                                    All status
                                                </SelectItem>
                                                <SelectItem value="1">
                                                    Active
                                                </SelectItem>
                                                <SelectItem value="0">
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
                open={deactivatingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeactivatingId(null);
                    }
                }}
                title="Deactivate driver?"
                description="The driver account will be marked inactive. Assignments are kept."
                confirmLabel="Deactivate"
                processing={processing}
                onConfirm={() => {
                    if (deactivatingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(DriverController.destroy.url(deactivatingId), {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            setDeactivatingId(null);
                        },
                    });
                }}
            />
        </>
    );
}

PlatformDriversIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Drivers', href: '/platform/drivers' },
    ],
};
