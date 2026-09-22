import { Link } from '@inertiajs/react';
import { CalendarRange, Clock3, Plus, Search, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ShiftBranchEmptyState } from '@/components/admin/shift-branch-empty-state';
import { ShiftBranchPicker } from '@/components/admin/shift-branch-picker';
import { ShiftCoverageMap } from '@/components/admin/shift-coverage-map';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftTimeBar } from '@/components/admin/shift-time-bar';
import { toWallClockDate, useAppDateTime } from '@/lib/datetime';
import {
    SHIFT_AUDIENCE_OPTIONS,
    assignmentMatchesAudience,
    templateMatchesAudience,
    type BranchOption,
    type ShiftAssignmentRow,
    type ShiftAudience,
    type ShiftTemplateOption,
    type ShiftWorkspaceStats,
} from '@/components/admin/workforce-types';
import { StatCard } from '@/components/portal/stat-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    durationLabel,
    formatClock,
    formatDayRange,
    formatLiveClock,
    formatLiveDate,
    initials,
    windowCoversNow,
} from '@/lib/shift-format';
import { cn } from '@/lib/utils';

type ShiftBoardProps = {
    kicker: string;
    title: string;
    description: string;
    templates: ShiftTemplateOption[];
    scheduled?: ShiftAssignmentRow[];
    onDuty: ShiftAssignmentRow[];
    stats?: ShiftWorkspaceStats;
    createTemplateHref: string;
    assignHref: string;
    templateEditHref?: (template: ShiftTemplateOption) => string | null;
    assignmentEditHref?: (assignment: ShiftAssignmentRow) => string | null;
    selectedBranch?: BranchOption | null;
    selectedBranchId?: number | null;
    branchOptionsUrl?: string | null;
    onSelectBranch?: (branchId: number) => void;
};

const PEOPLE_PAGE = 20;

export function ShiftBoard({
    kicker,
    title,
    description,
    templates,
    scheduled = [],
    onDuty,
    createTemplateHref,
    assignHref,
    templateEditHref,
    assignmentEditHref,
    selectedBranch = null,
    selectedBranchId = null,
    branchOptionsUrl = null,
    onSelectBranch,
}: ShiftBoardProps) {
    const { settings } = useAppDateTime();
    const [query, setQuery] = useState('');
    const [audienceKind, setAudienceKind] = useState<ShiftAudience>('all');
    const [visiblePeople, setVisiblePeople] = useState(PEOPLE_PAGE);
    const [selectedShiftId, setSelectedShiftId] = useState<number | null>(null);
    const [now, setNow] = useState(() =>
        toWallClockDate(new Date(), settings.timezone),
    );
    const needle = query.trim().toLowerCase();

    useEffect(() => {
        const tick = () =>
            setNow(toWallClockDate(new Date(), settings.timezone));

        tick();
        const timer = window.setInterval(tick, 30_000);

        return () => window.clearInterval(timer);
    }, [settings.timezone]);

    useEffect(() => {
        setSelectedShiftId(null);
        setQuery('');
        setAudienceKind('all');
        setVisiblePeople(PEOPLE_PAGE);
    }, [selectedBranchId]);

    const audienceTemplates = useMemo(
        () =>
            templates.filter((template) =>
                templateMatchesAudience(template, audienceKind),
            ),
        [audienceKind, templates],
    );

    useEffect(() => {
        if (
            selectedShiftId !== null &&
            !audienceTemplates.some((template) => template.id === selectedShiftId)
        ) {
            setSelectedShiftId(null);
        }
    }, [audienceTemplates, selectedShiftId]);

    const selectedTemplate =
        audienceTemplates.find((template) => template.id === selectedShiftId) ??
        null;
    const scopedTemplates = selectedTemplate
        ? [selectedTemplate]
        : audienceTemplates;
    const audienceOnDuty = useMemo(
        () =>
            onDuty.filter((row) =>
                assignmentMatchesAudience(row, audienceKind, templates),
            ),
        [audienceKind, onDuty, templates],
    );
    const audienceScheduled = useMemo(
        () =>
            scheduled.filter((row) =>
                assignmentMatchesAudience(row, audienceKind, templates),
            ),
        [audienceKind, scheduled, templates],
    );
    const scopedOnDuty = selectedShiftId
        ? audienceOnDuty.filter((row) => row.shift_template_id === selectedShiftId)
        : audienceOnDuty;
    const scopedScheduled = selectedShiftId
        ? audienceScheduled.filter(
              (row) => row.shift_template_id === selectedShiftId,
          )
        : audienceScheduled;

    const visibleScheduled = useMemo(
        () =>
            scopedScheduled.filter((row) => {
                if (needle === '') {
                    return true;
                }

                return [
                    row.user_name,
                    row.shift_template_name,
                    row.days_label,
                    row.branch_name,
                    row.user_type_label,
                    row.role_label,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase()
                    .includes(needle);
            }),
        [needle, scopedScheduled],
    );

    useEffect(() => {
        setVisiblePeople(PEOPLE_PAGE);
    }, [audienceKind, needle, selectedShiftId]);

    const hasMorePeople = visiblePeople < visibleScheduled.length;

    const liveOnDuty = useMemo(
        () =>
            scopedOnDuty.filter((row) =>
                windowCoversNow(row.start_time, row.end_time, now),
            ),
        [now, scopedOnDuty],
    );

    const groupedTemplates = useMemo(
        () => groupByBranch(scopedTemplates),
        [scopedTemplates],
    );

    const peopleGroups = useMemo(
        () => groupByUserType(visibleScheduled),
        [visibleScheduled],
    );

    const shownGroups = useMemo(() => {
        let remaining = visiblePeople;
        const groups: {
            label: string;
            total: number;
            items: ShiftAssignmentRow[];
        }[] = [];

        for (const group of peopleGroups) {
            if (remaining <= 0) {
                break;
            }

            const items = group.items.slice(0, remaining);
            remaining -= items.length;
            groups.push({
                label: group.label,
                total: group.items.length,
                items,
            });
        }

        return groups;
    }, [peopleGroups, visiblePeople]);

    const waitingForBranch = Boolean(
        onSelectBranch && branchOptionsUrl && !selectedBranchId,
    );

    const branchPicker =
        onSelectBranch && branchOptionsUrl ? (
            <ShiftBranchPicker
                optionsUrl={branchOptionsUrl}
                value={selectedBranchId ? String(selectedBranchId) : ''}
                selectedLabel={selectedBranch?.name}
                onChange={(branchId) => onSelectBranch(Number(branchId))}
            />
        ) : null;

    const typeFilter = (
        <div className="flex w-full max-w-[14rem] flex-col gap-1.5">
            <Label>Type</Label>
            <Select
                value={audienceKind}
                onValueChange={(value) =>
                    setAudienceKind(value as ShiftAudience)
                }
            >
                <SelectTrigger className="w-full">
                    <SelectValue placeholder="All" />
                </SelectTrigger>
                <SelectContent>
                    {SHIFT_AUDIENCE_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );

    return (
        <div
            className={cn(
                'flex w-full flex-col p-2 md:p-4',
                waitingForBranch ? 'gap-5' : 'gap-6',
            )}
        >
            <ShiftPageHeader
                kicker={kicker}
                title={title}
                description={description}
                actions={
                    waitingForBranch ? null : (
                        <>
                            <div className="border-border/80 bg-card hidden items-center gap-3 rounded-full border px-3 py-1.5 sm:flex">
                                <span className="relative flex size-2">
                                    <span className="bg-primary absolute inline-flex size-full animate-ping rounded-full opacity-60" />
                                    <span className="bg-primary relative inline-flex size-2 rounded-full" />
                                </span>
                                <div className="leading-tight">
                                    <p className="text-sm font-semibold tabular-nums">
                                        {formatLiveClock(now)}
                                    </p>
                                    <p className="text-muted-foreground text-[11px]">
                                        {formatLiveDate(now)}
                                    </p>
                                </div>
                            </div>
                            <Button variant="secondary" asChild>
                                <Link href={assignHref}>Assign to shift</Link>
                            </Button>
                            <Button asChild>
                                <Link href={createTemplateHref}>
                                    <Plus className="size-4" />
                                    New shift
                                </Link>
                            </Button>
                        </>
                    )
                }
            />

            {waitingForBranch && branchOptionsUrl && onSelectBranch ? (
                <ShiftBranchEmptyState
                    picker={
                        <ShiftBranchPicker
                            optionsUrl={branchOptionsUrl}
                            value=""
                            selectedLabel={null}
                            size="lg"
                            className="max-w-none"
                            onChange={(branchId) =>
                                onSelectBranch(Number(branchId))
                            }
                        />
                    }
                />
            ) : (
                <>
                    <div className="flex flex-wrap items-end gap-3">
                        {branchPicker}
                        {typeFilter}
                    </div>

                    <ShiftCoverageMap
                        templates={audienceTemplates}
                        now={now}
                        selectedTemplateId={selectedShiftId}
                        onSelectTemplate={setSelectedShiftId}
                    />

                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <StatCard
                            icon={Users}
                            label="On duty now"
                            value={String(liveOnDuty.length)}
                            hint={
                                selectedTemplate
                                    ? `On ${selectedTemplate.name} now`
                                    : liveOnDuty.length === 1
                                      ? 'Person inside a live window'
                                      : 'People inside a live window'
                            }
                        />
                        {selectedTemplate ? (
                            <StatCard
                                icon={Clock3}
                                label={selectedTemplate.name}
                                value={`${formatClock(selectedTemplate.start_time)} – ${formatClock(selectedTemplate.end_time)}`}
                                valueClassName="text-lg"
                                hint={`${durationLabel(selectedTemplate.start_time, selectedTemplate.end_time) || 'Same-day window'}${
                                    windowCoversNow(
                                        selectedTemplate.start_time,
                                        selectedTemplate.end_time,
                                        now,
                                    )
                                        ? ' · Live'
                                        : selectedTemplate.is_active
                                          ? ' · Active'
                                          : ' · Inactive'
                                }`}
                            />
                        ) : (
                            <StatCard
                                icon={Clock3}
                                label="Shifts"
                                value={String(scopedTemplates.length)}
                                hint={`${scopedTemplates.filter((template) => template.is_active).length} active`}
                            />
                        )}
                        <StatCard
                            icon={CalendarRange}
                            label="Scheduled people"
                            value={String(scopedScheduled.length)}
                            hint={
                                selectedTemplate
                                    ? 'Assigned to this shift'
                                    : 'Personal duty periods'
                            }
                        />
                    </div>

                    <section className="bg-card overflow-hidden rounded-2xl border">
                        <header className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                            <div>
                                <h2 className="text-base font-semibold tracking-tight">
                                    On duty now
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    {selectedTemplate
                                        ? `Live people on ${selectedTemplate.name}.`
                                        : 'Live coverage from today’s scheduled days.'}
                                </p>
                            </div>
                            <Button size="sm" variant="outline" asChild>
                                <Link href={assignHref}>Assign someone</Link>
                            </Button>
                        </header>
                        {liveOnDuty.length === 0 ? (
                            <div className="text-muted-foreground px-5 py-8 text-sm">
                                {selectedTemplate
                                    ? `Nobody is inside ${selectedTemplate.name} right now.`
                                    : 'Nobody is inside a duty window right now.'}
                            </div>
                        ) : (
                            <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                                {liveOnDuty.map((person) => (
                                    <article
                                        key={person.id}
                                        className="flex items-center gap-3 rounded-xl border px-3 py-3"
                                    >
                                        <div className="relative">
                                            <Avatar className="size-10">
                                                {person.avatar_url ? (
                                                    <AvatarImage
                                                        src={person.avatar_url}
                                                        alt={
                                                            person.user_name ??
                                                            ''
                                                        }
                                                    />
                                                ) : null}
                                                <AvatarFallback>
                                                    {initials(
                                                        person.user_name ??
                                                            '?',
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span className="bg-primary ring-card absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full ring-2" />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {person.user_name}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {person.shift_template_name} ·{' '}
                                                {formatClock(person.start_time)}
                                                –
                                                {formatClock(person.end_time)}
                                            </p>
                                            <p className="text-muted-foreground truncate text-xs">
                                                {person.role_label ??
                                                    person.user_type_label ??
                                                    person.branch_name}
                                            </p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="bg-card overflow-hidden rounded-2xl border">
                        <header className="flex flex-wrap items-end justify-between gap-3 border-b px-5 py-4">
                            <div>
                                <h2 className="text-base font-semibold tracking-tight">
                                    Scheduled people
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    {selectedTemplate
                                        ? `People assigned to ${selectedTemplate.name}.`
                                        : 'Search and filter — the list stays in this panel.'}
                                </p>
                            </div>
                            <span className="text-muted-foreground text-xs tabular-nums">
                                {visibleScheduled.length}
                            </span>
                        </header>
                        <div className="flex flex-wrap items-center gap-2 border-b px-5 py-3">
                            <div className="relative min-w-48 max-w-md flex-1">
                                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={query}
                                    onChange={(event) =>
                                        setQuery(event.target.value)
                                    }
                                    placeholder="Search people"
                                    className="h-9 pl-9"
                                />
                            </div>
                            {audienceTemplates.length > 0 ? (
                                <Select
                                    value={
                                        selectedShiftId === null
                                            ? 'all'
                                            : String(selectedShiftId)
                                    }
                                    onValueChange={(value) =>
                                        setSelectedShiftId(
                                            value === 'all'
                                                ? null
                                                : Number(value),
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        size="sm"
                                        className="w-44 max-w-full"
                                    >
                                        <SelectValue placeholder="All shifts" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All shifts
                                        </SelectItem>
                                        {audienceTemplates.map((template) => (
                                            <SelectItem
                                                key={template.id}
                                                value={String(template.id)}
                                            >
                                                {template.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            ) : null}
                        </div>
                        {visibleScheduled.length === 0 ? (
                            <div className="px-5 py-8">
                                <p className="font-medium">
                                    {selectedTemplate
                                        ? `Nobody on ${selectedTemplate.name}`
                                        : query
                                          ? 'No matching people'
                                          : 'Nobody scheduled'}
                                </p>
                                <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                    {selectedTemplate
                                        ? 'Assign someone to this shift and date range.'
                                        : query
                                          ? 'Try a different name or shift.'
                                          : 'Assign a person, a date range, and a usual shift.'}
                                </p>
                                {!query ? (
                                    <Button size="sm" className="mt-3" asChild>
                                        <Link href={assignHref}>
                                            Assign to shift
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        ) : (
                            <div className="max-h-[28rem] overflow-y-auto">
                                {shownGroups.map((group) => (
                                    <div key={group.label}>
                                        {peopleGroups.length > 1 ? (
                                            <p className="bg-muted/70 text-muted-foreground sticky top-0 z-10 px-5 py-1.5 text-[11px] font-medium tracking-[0.14em] uppercase">
                                                {group.label}
                                                <span className="ml-2 tabular-nums">
                                                    {group.total}
                                                </span>
                                            </p>
                                        ) : null}
                                        <ul className="divide-y">
                                            {group.items.map((row) => (
                                                <PersonRow
                                                    key={row.id}
                                                    row={row}
                                                    editHref={
                                                        assignmentEditHref?.(
                                                            row,
                                                        ) ?? null
                                                    }
                                                />
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                                {hasMorePeople ? (
                                    <div className="border-t px-5 py-3">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                setVisiblePeople(
                                                    (count) =>
                                                        count + PEOPLE_PAGE,
                                                )
                                            }
                                        >
                                            Show more
                                            <span className="text-muted-foreground ml-1 tabular-nums">
                                                {visibleScheduled.length -
                                                    visiblePeople}{' '}
                                                left
                                            </span>
                                        </Button>
                                    </div>
                                ) : null}
                            </div>
                        )}
                    </section>

                    <section className="space-y-5">
                        <div className="flex items-end justify-between gap-3">
                            <div>
                                <h2 className="text-base font-semibold tracking-tight">
                                    Shifts
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    {selectedTemplate
                                        ? 'This named window at this store.'
                                        : 'Named start and end times on the same day.'}
                                </p>
                            </div>
                            <span className="text-muted-foreground text-xs tabular-nums">
                                {scopedTemplates.length}
                            </span>
                        </div>
                        {scopedTemplates.length === 0 ? (
                            <EmptyPanel
                                icon={Clock3}
                                title="No shifts yet"
                                body="Create a named shift, then assign people to their own dates."
                                actionHref={createTemplateHref}
                                actionLabel="New shift"
                            />
                        ) : (
                            groupedTemplates.map((group) => (
                                <div key={group.label} className="space-y-3">
                                    {groupedTemplates.length > 1 ? (
                                        <p className="text-muted-foreground text-[11px] font-medium tracking-[0.16em] uppercase">
                                            {group.label}
                                        </p>
                                    ) : null}
                                    <div
                                        className={cn(
                                            'grid gap-3',
                                            selectedTemplate
                                                ? 'md:max-w-xl md:grid-cols-1'
                                                : 'md:grid-cols-2 xl:grid-cols-3',
                                        )}
                                    >
                                        {group.items.map((template) => (
                                            <TemplateCard
                                                key={template.id}
                                                template={template}
                                                now={now}
                                                editHref={templateEditHref?.(
                                                    template,
                                                )}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </section>
                </>
            )}
        </div>
    );
}

function PersonRow({
    row,
    editHref,
}: {
    row: ShiftAssignmentRow;
    editHref?: string | null;
}) {
    return (
        <li className="flex items-center gap-3 px-5 py-2.5">
            <Avatar className="size-8">
                {row.avatar_url ? (
                    <AvatarImage
                        src={row.avatar_url}
                        alt={row.user_name ?? ''}
                    />
                ) : null}
                <AvatarFallback className="text-[11px]">
                    {initials(row.user_name ?? '?')}
                </AvatarFallback>
            </Avatar>
            <div className="min-w-0 flex-1">
                <div className="flex min-w-0 items-center gap-2">
                    <p className="truncate text-sm font-medium">
                        {row.user_name}
                    </p>
                    {row.role_label ? (
                        <Badge
                            variant="secondary"
                            className="shrink-0 font-normal"
                        >
                            {row.role_label}
                        </Badge>
                    ) : null}
                </div>
                <p className="text-muted-foreground truncate text-xs">
                    {row.shift_template_name} · {formatClock(row.start_time)}–
                    {formatClock(row.end_time)}
                    {row.days_label ? ` · ${row.days_label}` : ''}
                </p>
            </div>
            <p className="text-muted-foreground hidden shrink-0 text-xs sm:block">
                {formatDayRange(row.start_date, row.end_date)}
            </p>
            {editHref ? (
                <Button size="sm" variant="ghost" asChild>
                    <Link href={editHref}>Edit</Link>
                </Button>
            ) : null}
        </li>
    );
}

function TemplateCard({
    template,
    now,
    editHref,
}: {
    template: ShiftTemplateOption;
    now: Date;
    editHref?: string | null;
}) {
    const live = windowCoversNow(template.start_time, template.end_time, now);

    return (
        <article className="bg-card flex flex-col gap-4 rounded-2xl border p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate font-medium">{template.name}</p>
                    <p className="mt-1 text-lg font-semibold tracking-tight tabular-nums">
                        {formatClock(template.start_time)}
                        <span className="text-muted-foreground mx-1.5 text-sm font-normal">
                            –
                        </span>
                        {formatClock(template.end_time)}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1.5">
                    {template.audience && template.audience !== 'all' ? (
                        <Badge variant="outline" className="font-normal">
                            {template.audience_label ?? template.audience}
                        </Badge>
                    ) : null}
                    {live ? (
                        <Badge>Live</Badge>
                    ) : (
                        <Badge
                            variant={
                                template.is_active ? 'secondary' : 'outline'
                            }
                        >
                            {template.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    )}
                </div>
            </div>
            <ShiftTimeBar
                start={template.start_time}
                end={template.end_time}
            />
            <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
                <p className="text-muted-foreground">
                    {template.branch_name ?? template.owner_type_label}
                    {' · '}
                    {durationLabel(template.start_time, template.end_time)}
                    {' · '}
                    {template.scheduled_count ?? 0} scheduled
                </p>
                {editHref ? (
                    <Button size="sm" variant="outline" asChild>
                        <Link href={editHref}>Edit</Link>
                    </Button>
                ) : null}
            </div>
        </article>
    );
}

function groupByBranch(
    templates: ShiftTemplateOption[],
): { label: string; items: ShiftTemplateOption[] }[] {
    const labels = new Set(
        templates.map(
            (template) =>
                template.branch_name ?? template.owner_type_label ?? 'Platform',
        ),
    );

    if (labels.size <= 1) {
        return [{ label: [...labels][0] ?? 'Windows', items: templates }];
    }

    const groups = new Map<string, ShiftTemplateOption[]>();

    for (const template of templates) {
        const label =
            template.branch_name ?? template.owner_type_label ?? 'Platform';
        const items = groups.get(label) ?? [];
        items.push(template);
        groups.set(label, items);
    }

    return [...groups.entries()].map(([label, items]) => ({ label, items }));
}

function groupByUserType(
    rows: ShiftAssignmentRow[],
): { label: string; items: ShiftAssignmentRow[] }[] {
    const groups = new Map<string, ShiftAssignmentRow[]>();

    for (const row of rows) {
        const label = row.user_type_label || row.user_type || 'People';
        const items = groups.get(label) ?? [];
        items.push(row);
        groups.set(label, items);
    }

    return [...groups.entries()].map(([label, items]) => ({ label, items }));
}

function EmptyPanel({
    icon: Icon,
    title,
    body,
    actionHref,
    actionLabel,
}: {
    icon: typeof Clock3;
    title: string;
    body: string;
    actionHref: string;
    actionLabel: string;
}) {
    return (
        <div className="bg-card flex flex-col items-start gap-3 rounded-2xl border px-5 py-8">
            <div className="bg-muted flex size-11 items-center justify-center rounded-full">
                <Icon className="text-muted-foreground size-5" />
            </div>
            <div>
                <p className="font-medium">{title}</p>
                <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                    {body}
                </p>
            </div>
            <Button size="sm" asChild>
                <Link href={actionHref}>{actionLabel}</Link>
            </Button>
        </div>
    );
}
