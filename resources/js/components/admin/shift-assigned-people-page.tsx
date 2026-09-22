import { Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import { type ReactNode, useMemo } from 'react';
import { ShiftBranchEmptyState } from '@/components/admin/shift-branch-empty-state';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type { AssignedShiftPersonRow } from '@/components/admin/workforce-types';
import { AppDate } from '@/components/app-datetime';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatClock, initials } from '@/lib/shift-format';
import type { QueryParams } from '@/wayfinder';

type TemplateOption = { id: number; name: string };
type PeopleKindOption = { value: string; label: string };

export type AssignedPeopleTablePayload = DataTablePayload<AssignedShiftPersonRow> & {
    filterOptions?: {
        templates?: TemplateOption[];
        people_kinds?: PeopleKindOption[];
    };
};

export function ShiftAssignedPeoplePage({
    kicker,
    title,
    description,
    tablePayload,
    indexUrl,
    extraQuery,
    assignHref,
    editHref,
    waitingForBranch = false,
    branchPicker = null,
}: {
    kicker: string;
    title: string;
    description: string;
    tablePayload: AssignedPeopleTablePayload;
    indexUrl: string;
    extraQuery?: QueryParams;
    assignHref: string;
    editHref: (row: AssignedShiftPersonRow) => string;
    waitingForBranch?: boolean;
    branchPicker?: ReactNode;
}) {
    const query = useDataTableQuery({
        indexUrl,
        extraQuery,
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                shift_template_id:
                    tablePayload.filters.shift_template_id ?? 'all',
                people_kind: tablePayload.filters.people_kind ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const templates = tablePayload.filterOptions?.templates ?? [];
    const peopleKinds = tablePayload.filterOptions?.people_kinds ?? [];

    const columns = useMemo<ColumnDef<AssignedShiftPersonRow>[]>(
        () => [
            {
                id: 'person',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Person"
                        sortable={false}
                    />
                ),
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-9">
                            <AvatarImage
                                src={row.original.avatar_url ?? undefined}
                                alt={row.original.user_name ?? ''}
                            />
                            <AvatarFallback>
                                {initials(row.original.user_name ?? '?')}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <DataTableTruncated className="font-medium">
                                {row.original.user_name ?? '—'}
                            </DataTableTruncated>
                            {row.original.branch_name ? (
                                <DataTableTruncated className="text-muted-foreground text-xs">
                                    {row.original.branch_name}
                                </DataTableTruncated>
                            ) : null}
                        </div>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                id: 'dates',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Dates"
                        sortable
                        sortKey="start_date"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <AssignedDates
                        start={row.original.start_date}
                        end={row.original.end_date}
                    />
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'shift_template_name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Shift"
                        sortable={false}
                    />
                ),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <DataTableTruncated>
                            {row.original.shift_template_name ?? '—'}
                        </DataTableTruncated>
                        <DataTableTruncated className="text-muted-foreground text-xs">
                            {formatClock(row.original.start_time)}–
                            {formatClock(row.original.end_time)}
                        </DataTableTruncated>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: editHref(row.original),
                            },
                        ]}
                    />
                ),
            },
        ],
        [editHref, query.sort, query.toggleSort],
    );

    return (
        <div className="flex w-full flex-col gap-6 p-2 md:p-4">
            <ShiftPageHeader
                kicker={kicker}
                title={title}
                description={description}
                actions={
                    waitingForBranch ? null : (
                        <Button asChild>
                            <Link href={assignHref}>Assign to shift</Link>
                        </Button>
                    )
                }
            />

            {waitingForBranch ? (
                <ShiftBranchEmptyState picker={branchPicker} />
            ) : (
                <>
                    {branchPicker}

                    <DataTable
                        columns={columns}
                        payload={tablePayload}
                        perPage={query.perPage}
                        onPerPageChange={query.changePerPage}
                        emptyMessage="Nobody is assigned to a shift yet."
                        toolbar={
                            <DataTableToolbar
                                search={query.search}
                                onSearchChange={query.setSearch}
                                searchPlaceholder="Search person or shift…"
                                onClear={query.clearFilters}
                                filters={
                                    <>
                                        {templates.length > 0 ? (
                                            <div className="grid gap-2">
                                                <Label>Shift</Label>
                                                <Select
                                                    value={String(
                                                        query.filters
                                                            .shift_template_id ??
                                                            'all',
                                                    )}
                                                    onValueChange={(value) =>
                                                        query.setFilter(
                                                            'shift_template_id',
                                                            value === 'all'
                                                                ? null
                                                                : value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-44">
                                                        <SelectValue placeholder="All shifts" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all">
                                                            All shifts
                                                        </SelectItem>
                                                        {templates.map(
                                                            (template) => (
                                                                <SelectItem
                                                                    key={
                                                                        template.id
                                                                    }
                                                                    value={String(
                                                                        template.id,
                                                                    )}
                                                                >
                                                                    {
                                                                        template.name
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        ) : null}
                                        {peopleKinds.length > 0 ? (
                                            <div className="grid gap-2">
                                                <Label>People</Label>
                                                <Select
                                                    value={String(
                                                        query.filters
                                                            .people_kind ??
                                                            'all',
                                                    )}
                                                    onValueChange={(value) =>
                                                        query.setFilter(
                                                            'people_kind',
                                                            value === 'all'
                                                                ? null
                                                                : value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-40">
                                                        <SelectValue placeholder="All people" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {peopleKinds.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        ) : null}
                                    </>
                                }
                            />
                        }
                    />
                </>
            )}
        </div>
    );
}

function AssignedDates({
    start,
    end,
}: {
    start: string | null;
    end: string | null;
}) {
    if (!start || !end) {
        return <span>—</span>;
    }

    if (start === end) {
        return <AppDate value={start} />;
    }

    return (
        <span className="whitespace-nowrap">
            <AppDate value={start} />
            <span className="text-muted-foreground"> – </span>
            <AppDate value={end} />
        </span>
    );
}
