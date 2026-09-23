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
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type DriverRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    vehicle_type_label: string | null;
    availability_status_label: string | null;
    assignment_status_label: string | null;
};

type Branch = { id: number; name: string; slug: string };

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
                header: 'Phone',
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
                    emptyMessage="No drivers assigned to this branch."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search drivers…"
                            onClear={query.clearFilters}
                        />
                    }
                />
            </div>
        </>
    );
}
