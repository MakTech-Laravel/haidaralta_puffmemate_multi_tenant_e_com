import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CalendarClock, Eye, Pencil } from 'lucide-react';
import { useMemo } from 'react';
import BranchDriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
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

type DriverRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    avatar_url: string | null;
    vehicle_type_label: string | null;
    availability_status_label: string | null;
    assignment_status_label: string | null;
};

type Branch = { id: number; name: string; slug: string };

type FilterOption = { value: string; label: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function BranchDriversIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<DriverRow>;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Drivers',
                href: BranchDriverController.index.url(branch.slug),
            },
        ],
    });

    const availabilityOptions = (tablePayload.filterOptions
        ?.availability_status ?? []) as FilterOption[];
    const assignmentOptions = (tablePayload.filterOptions
        ?.assignment_status ?? []) as FilterOption[];
    const vehicleOptions = (tablePayload.filterOptions?.vehicle_type ??
        []) as FilterOption[];
    const statusOptions = (tablePayload.filterOptions?.is_active ??
        []) as FilterOption[];

    const query = useDataTableQuery({
        indexUrl: BranchDriverController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                is_active: tablePayload.filters.is_active ?? null,
                availability_status:
                    tablePayload.filters.availability_status ?? null,
                assignment_status:
                    tablePayload.filters.assignment_status ?? null,
                vehicle_type: tablePayload.filters.vehicle_type ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<DriverRow>[]>(
        () => [
            {
                id: 'avatar',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Driver"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
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
                id: 'assignment',
                header: 'Assignment',
                cell: ({ row }) => row.original.assignment_status_label ?? '—',
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
                                href: BranchDriverController.show.url({
                                    branch: branch.slug,
                                    user: row.original.id,
                                }),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: BranchDriverController.edit.url({
                                    branch: branch.slug,
                                    user: row.original.id,
                                }),
                            },
                            {
                                label: 'Assign to shift',
                                icon: CalendarClock,
                                href: ShiftAssignmentController.create.url(
                                    branch.slug,
                                    {
                                        query: {
                                            people_kind: 'driver',
                                            user_id: row.original.id,
                                        },
                                    },
                                ),
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
            <Head title="Drivers" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Drivers"
                        description={`Drivers assigned to ${branch.name}`}
                    />
                    <Button asChild>
                        <Link
                            href={BranchDriverController.create.url(
                                branch.slug,
                            )}
                        >
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
                                                {availabilityOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Assignment</Label>
                                        <Select
                                            value={String(
                                                query.filters
                                                    .assignment_status ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'assignment_status',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Assignment" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {assignmentOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Vehicle</Label>
                                        <Select
                                            value={String(
                                                query.filters.vehicle_type ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'vehicle_type',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-[10rem]">
                                                <SelectValue placeholder="Vehicle" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {vehicleOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
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
                                                {statusOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
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
