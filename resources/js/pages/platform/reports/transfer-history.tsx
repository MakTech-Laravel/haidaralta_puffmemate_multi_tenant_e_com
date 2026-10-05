import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import TransferController from '@/actions/App/Http/Controllers/Platform/TransferController';
import type { TransferRow } from '@/components/admin/workforce-types';
import { AppDate } from '@/components/app-datetime';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function roleLabel(role: string | null | undefined): string {
    if (!role) {
        return '—';
    }

    return role.replaceAll('-', ' ');
}

function personHref(row: TransferRow): string {
    return row.subject_type === 'driver'
        ? DriverController.show.url(row.user_id)
        : BranchStaffController.show.url(row.user_id);
}

export default function PlatformTransferHistory({
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
                id: 'person',
                header: 'Person',
                meta: { truncate: false },
                cell: ({ row }) => {
                    const name = row.original.user_name ?? '—';
                    const href = personHref(row.original);

                    return (
                        <Link
                            href={href}
                            className="hover:text-primary flex min-w-0 items-center gap-3"
                        >
                            <Avatar className="size-10">
                                <AvatarImage
                                    src={row.original.avatar_url ?? undefined}
                                    alt={name}
                                />
                                <AvatarFallback>
                                    {initials(name)}
                                </AvatarFallback>
                            </Avatar>
                            <DataTableTruncated className="font-medium">
                                {name}
                            </DataTableTruncated>
                        </Link>
                    );
                },
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
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="min-w-0 space-y-0.5">
                        <DataTableTruncated>
                            {row.original.from_branch_name ?? '—'} →{' '}
                            {row.original.to_branch_name ?? '—'}
                        </DataTableTruncated>
                        <DataTableTruncated className="text-muted-foreground text-xs capitalize">
                            {roleLabel(row.original.from_role)} →{' '}
                            {roleLabel(row.original.to_role)}
                        </DataTableTruncated>
                    </div>
                ),
            },
            {
                id: 'reason',
                header: 'Reason',
                meta: { truncate: false },
                cell: ({ row }) =>
                    row.original.reason ? (
                        <DataTableTruncated className="max-w-[14rem]">
                            {row.original.reason}
                        </DataTableTruncated>
                    ) : (
                        <span className="text-muted-foreground">—</span>
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
                cell: ({ row }) => (
                    <AppDate value={row.original.effective_date} />
                ),
            },
        ],
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Transfer history" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Transfer history"
                    description="Branch and role moves for drivers and branch staff"
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
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

PlatformTransferHistory.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Transfer history',
            href: '/platform/reports/transfer-history',
        },
    ],
};
