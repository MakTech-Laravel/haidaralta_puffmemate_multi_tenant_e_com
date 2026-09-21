import { Head } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye } from 'lucide-react';
import { useMemo } from 'react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import TransferController from '@/actions/App/Http/Controllers/Platform/TransferController';
import type { TransferRow } from '@/components/admin/workforce-types';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type BranchOption = { id: number; name: string; slug: string };
type Option = { value: string; label: string };

export default function PlatformTransfersIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<TransferRow> & {
        filterOptions?: {
            branches?: BranchOption[];
            subject_types?: Option[];
            transfer_types?: Option[];
        };
    };
}) {
    const query = useDataTableQuery({
        indexUrl: TransferController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                subject_type: tablePayload.filters.subject_type ?? 'all',
                transfer_type: tablePayload.filters.transfer_type ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const branches = tablePayload.filterOptions?.branches ?? [];
    const subjectTypes = tablePayload.filterOptions?.subject_types ?? [];
    const transferTypes = tablePayload.filterOptions?.transfer_types ?? [];

    const columns = useMemo<ColumnDef<TransferRow>[]>(
        () => [
            {
                accessorKey: 'user_name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Person"
                        sortable={false}
                    />
                ),
            },
            {
                accessorKey: 'subject_type_label',
                header: 'Subject',
            },
            {
                accessorKey: 'transfer_type_label',
                header: 'Type',
                cell: ({ row }) => (
                    <Badge variant="secondary">
                        {row.original.transfer_type_label}
                    </Badge>
                ),
            },
            {
                id: 'from_to',
                header: 'From → To',
                cell: ({ row }) => (
                    <span>
                        {row.original.from_branch_name ?? '—'} →{' '}
                        {row.original.to_branch_name ?? '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'effective_date',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Effective"
                        sortable
                        sortKey="effective_date"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View person',
                                icon: Eye,
                                href:
                                    row.original.subject_type === 'driver'
                                        ? DriverController.show.url(
                                              row.original.user_id,
                                          )
                                        : BranchStaffController.show.url(
                                              row.original.user_id,
                                          ),
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
            <Head title="Transfers" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Transfers"
                    description="Branch and role moves for drivers and branch staff"
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No transfers found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search person, branch, or reason…"
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
                                            <SelectTrigger className="w-44">
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
                                        <Label>Subject</Label>
                                        <Select
                                            value={String(
                                                query.filters.subject_type ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'subject_type',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Subject" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All subjects
                                                </SelectItem>
                                                {subjectTypes.map((type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Type</Label>
                                        <Select
                                            value={String(
                                                query.filters.transfer_type ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'transfer_type',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All types
                                                </SelectItem>
                                                {transferTypes.map((type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
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

PlatformTransfersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Transfers', href: '/platform/transfers' },
    ],
};
