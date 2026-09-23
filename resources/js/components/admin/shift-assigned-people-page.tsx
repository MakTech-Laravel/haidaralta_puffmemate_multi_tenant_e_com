import { Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { format, parseISO, setMonth, setYear } from 'date-fns';
import { Pencil } from 'lucide-react';
import { type ReactNode, useMemo } from 'react';
import { ShiftBranchEmptyState } from '@/components/admin/shift-branch-empty-state';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type { AssignedShiftPersonRow } from '@/components/admin/workforce-types';
import {
    buildDataTableQuery,
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
import { initials } from '@/lib/shift-format';
import type { QueryParams } from '@/wayfinder';

type TemplateOption = { id: number; name: string };
type PeopleKindOption = { value: string; label: string };

export type AssignedPeopleTablePayload =
    DataTablePayload<AssignedShiftPersonRow> & {
        month: string;
        filterOptions?: {
            templates?: TemplateOption[];
            people_kinds?: PeopleKindOption[];
        };
    };

const MONTH_OPTIONS = Array.from({ length: 12 }, (_, index) => ({
    value: String(index),
    label: format(new Date(2020, index, 1), 'MMMM'),
}));

function yearOptions(): string[] {
    const endYear = new Date().getFullYear() + 2;

    return Array.from({ length: endYear - 2020 + 1 }, (_, index) =>
        String(2020 + index),
    );
}

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
    const month = tablePayload.month;
    const monthDate = parseISO(`${month}-01`);
    const years = useMemo(() => yearOptions(), []);

    const query = useDataTableQuery({
        indexUrl,
        extraQuery: () => ({
            ...extraQuery,
            month,
        }),
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

    const visitMonth = (nextMonth: string): void => {
        router.get(
            indexUrl,
            {
                ...extraQuery,
                month: nextMonth,
                ...buildDataTableQuery({
                    filter: query.filters,
                    sort: query.sort,
                    per_page: query.perPage,
                    page: 1,
                }),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const columns = useMemo<ColumnDef<AssignedShiftPersonRow>[]>(
        () => [
            {
                id: 'person',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Person"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
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
                            <DataTableTruncated className="text-muted-foreground text-xs tabular-nums">
                                {row.original.phone || '—'}
                            </DataTableTruncated>
                        </div>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                id: 'type',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Type"
                        sortable={false}
                    />
                ),
                cell: ({ row }) => (
                    <span className="text-sm">
                        {row.original.people_kind_label ?? '—'}
                    </span>
                ),
            },
            {
                id: 'assigned_dates',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Assigned dates"
                        sortable={false}
                    />
                ),
                cell: ({ row }) => (
                    <span className="font-mono text-sm whitespace-nowrap tabular-nums">
                        {row.original.assigned_dates_label || '—'}
                    </span>
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
                                label: 'Edit schedule',
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
                        emptyMessage="Nobody has duty days in this month."
                        toolbar={
                            <DataTableToolbar
                                search={query.search}
                                onSearchChange={query.setSearch}
                                searchPlaceholder="Search name or phone…"
                                onClear={query.clearFilters}
                                filters={
                                    <>
                                        <div className="grid gap-2">
                                            <Label>Month</Label>
                                            <div className="flex items-center gap-2">
                                                <Select
                                                    value={String(
                                                        monthDate.getMonth(),
                                                    )}
                                                    onValueChange={(value) =>
                                                        visitMonth(
                                                            format(
                                                                setMonth(
                                                                    monthDate,
                                                                    Number(
                                                                        value,
                                                                    ),
                                                                ),
                                                                'yyyy-MM',
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-36">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {MONTH_OPTIONS.map(
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
                                                <Select
                                                    value={String(
                                                        monthDate.getFullYear(),
                                                    )}
                                                    onValueChange={(value) =>
                                                        visitMonth(
                                                            format(
                                                                setYear(
                                                                    monthDate,
                                                                    Number(
                                                                        value,
                                                                    ),
                                                                ),
                                                                'yyyy-MM',
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-24">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {years.map((year) => (
                                                            <SelectItem
                                                                key={year}
                                                                value={year}
                                                            >
                                                                {year}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
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
