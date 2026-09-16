import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Building2, Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DriverBranchAssignSheet } from '@/components/admin/driver-branch-assign-sheet';
import {
    DataTable,
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

type DriverRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    avatar_url: string | null;
    vehicle_type_label: string | null;
    availability_status_label: string | null;
    effective_availability: string | null;
    effective_availability_label: string | null;
    branch_ids: number[];
    branches: { id: number; name: string; slug: string }[];
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

export default function PlatformDriversIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<DriverRow>;
}) {
    const [deactivatingId, setDeactivatingId] = useState<number | null>(null);
    const [managingDriver, setManagingDriver] = useState<DriverRow | null>(
        null,
    );
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
                id: 'avatar',
                header: 'Driver',
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
                id: 'vehicle',
                header: 'Vehicle',
                cell: ({ row }) => row.original.vehicle_type_label ?? '—',
            },
            {
                id: 'availability',
                header: 'Availability',
                cell: ({ row }) =>
                    row.original.effective_availability_label ?? '—',
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
                                <Badge key={branch.id} variant="secondary" asChild>
                                    <Link
                                        href={BranchController.show.url(
                                            branch.slug,
                                        )}
                                        className="hover:underline"
                                    >
                                        {branch.name}
                                    </Link>
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
                                label: 'Manage branches',
                                icon: Building2,
                                onSelect: () =>
                                    setManagingDriver(row.original),
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
        [],
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

            <DriverBranchAssignSheet
                open={managingDriver !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setManagingDriver(null);
                    }
                }}
                driverId={managingDriver?.id ?? null}
                selectedIds={managingDriver?.branch_ids ?? []}
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
