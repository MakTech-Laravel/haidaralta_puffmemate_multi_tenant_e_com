import { Link } from '@inertiajs/react';
import {
    CalendarRange,
    Clock3,
    Moon,
    Plus,
    Search,
    Users,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ShiftBranchPicker } from '@/components/admin/shift-branch-picker';
import { ShiftCoverageMap } from '@/components/admin/shift-coverage-map';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftPeriodSpan } from '@/components/admin/shift-period-span';
import { ShiftTimeBar } from '@/components/admin/shift-time-bar';
import type {
    BranchOption,
    ShiftAssignmentRow,
    ShiftPeriodOption,
    ShiftTemplateOption,
    ShiftWorkspaceStats,
} from '@/components/admin/workforce-types';
import { StatCard } from '@/components/portal/stat-card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

type ShiftFilter = 'all' | 'live' | 'overnight';

type ShiftBoardProps = {
    kicker: string;
    title: string;
    description: string;
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    onDuty: ShiftAssignmentRow[];
    stats: ShiftWorkspaceStats;
    createTemplateHref: string;
    createPeriodHref: string;
    assignHref: string;
    templateEditHref?: (template: ShiftTemplateOption) => string | null;
    periodEditHref?: (period: ShiftPeriodOption) => string | null;
    selectedBranch?: BranchOption | null;
    selectedBranchId?: number | null;
    branchOptionsUrl?: string | null;
    onSelectBranch?: (branchId: number) => void;
};

export function ShiftBoard({
    kicker,
    title,
    description,
    templates,
    periods,
    onDuty,
    stats,
    createTemplateHref,
    createPeriodHref,
    assignHref,
    templateEditHref,
    periodEditHref,
    selectedBranch = null,
    selectedBranchId = null,
    branchOptionsUrl = null,
    onSelectBranch,
}: ShiftBoardProps) {
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState<ShiftFilter>('all');
    const [now, setNow] = useState(() => new Date());
    const needle = query.trim().toLowerCase();

    useEffect(() => {
        const timer = window.setInterval(() => setNow(new Date()), 30_000);

        return () => window.clearInterval(timer);
    }, []);

    const visibleTemplates = useMemo(
        () =>
            templates.filter((template) => {
                if (filter === 'overnight' && !template.is_overnight) {
                    return false;
                }

                if (
                    filter === 'live' &&
                    !windowCoversNow(
                        template.start_time,
                        template.end_time,
                        template.is_overnight,
                        now,
                    )
                ) {
                    return false;
                }

                if (needle === '') {
                    return true;
                }

                return [
                    template.name,
                    template.branch_name,
                    template.owner_type_label,
                    template.start_time,
                    template.end_time,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase()
                    .includes(needle);
            }),
        [filter, needle, now, templates],
    );

    const visiblePeriods = useMemo(
        () =>
            periods.filter((period) => {
                if (needle === '') {
                    return true;
                }

                return [
                    period.name,
                    period.branch_name,
                    period.start_date,
                    period.end_date,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase()
                    .includes(needle);
            }),
        [needle, periods],
    );

    const groupedTemplates = useMemo(
        () => groupByBranch(visibleTemplates),
        [visibleTemplates],
    );

    return (
        <div className="flex w-full flex-col gap-6 p-2 md:p-4">
            <ShiftPageHeader
                kicker={kicker}
                title={title}
                description={description}
                actions={
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
                            <Link href={createPeriodHref}>New period</Link>
                        </Button>
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
                }
            />

            {onSelectBranch && branchOptionsUrl ? (
                <ShiftBranchPicker
                    optionsUrl={branchOptionsUrl}
                    value={selectedBranchId ? String(selectedBranchId) : ''}
                    selectedLabel={selectedBranch?.name}
                    onChange={(branchId) => onSelectBranch(Number(branchId))}
                />
            ) : null}

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    icon={Users}
                    label="On duty now"
                    value={String(stats.on_duty)}
                    hint={
                        stats.on_duty === 1
                            ? 'Person inside a live window'
                            : 'People inside a live window'
                    }
                />
                <StatCard
                    icon={Clock3}
                    label="Shifts"
                    value={String(stats.templates)}
                    hint={`${stats.active_templates} active`}
                />
                <StatCard
                    icon={CalendarRange}
                    label="Roster periods"
                    value={String(stats.periods)}
                    hint="Named date ranges"
                />
                <StatCard
                    icon={Moon}
                    label="Overnight"
                    value={String(stats.overnight)}
                    hint="Windows that wrap midnight"
                />
            </div>

            <section className="bg-card overflow-hidden rounded-2xl border">
                <header className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                    <div>
                        <h2 className="text-base font-semibold tracking-tight">
                            On duty now
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Live coverage from scheduled assignments.
                        </p>
                    </div>
                    <Button size="sm" variant="outline" asChild>
                        <Link href={assignHref}>Assign someone</Link>
                    </Button>
                </header>
                {onDuty.length === 0 ? (
                    <div className="text-muted-foreground px-5 py-8 text-sm">
                        Nobody is inside a duty window right now.
                    </div>
                ) : (
                    <div className="grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                        {onDuty.map((person) => (
                            <article
                                key={person.id}
                                className="flex items-center gap-3 rounded-xl border px-3 py-3"
                            >
                                <div className="relative">
                                    <Avatar className="size-10">
                                        {person.avatar_url ? (
                                            <AvatarImage
                                                src={person.avatar_url}
                                                alt={person.user_name ?? ''}
                                            />
                                        ) : null}
                                        <AvatarFallback>
                                            {initials(person.user_name ?? '?')}
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
                                        {formatClock(person.start_time)}–
                                        {formatClock(person.end_time)}
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {person.user_type_label ??
                                            person.branch_name}
                                    </p>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>

            <ShiftCoverageMap templates={templates} now={now} />

            <div className="flex flex-wrap items-center gap-3">
                <div className="relative min-w-56 flex-1 max-w-md">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Filter shifts and periods"
                        className="pl-9"
                    />
                </div>
                <div className="flex flex-wrap gap-1.5">
                    {(
                        [
                            ['all', 'All windows'],
                            ['live', 'Live now'],
                            ['overnight', 'Overnight'],
                        ] as const
                    ).map(([value, label]) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => setFilter(value)}
                            className={cn(
                                'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                                filter === value
                                    ? 'border-transparent bg-primary text-primary-foreground'
                                    : 'hover:bg-muted',
                            )}
                        >
                            {label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(20rem,1fr)]">
                <section className="space-y-5">
                    <div className="flex items-end justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold tracking-tight">
                                Shifts
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Named start and end times, including overnight.
                            </p>
                        </div>
                        <span className="text-muted-foreground text-xs tabular-nums">
                            {visibleTemplates.length}
                        </span>
                    </div>
                    {visibleTemplates.length === 0 ? (
                        <EmptyPanel
                            icon={Clock3}
                            title="No shifts yet"
                            body="Create a named shift, then assign people into a roster period."
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
                                <div className="grid gap-3 md:grid-cols-2">
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

                <section className="space-y-4">
                    <div className="flex items-end justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold tracking-tight">
                                Roster periods
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Date ranges assignments must stay inside.
                            </p>
                        </div>
                        <span className="text-muted-foreground text-xs tabular-nums">
                            {visiblePeriods.length}
                        </span>
                    </div>
                    {visiblePeriods.length === 0 ? (
                        <EmptyPanel
                            icon={CalendarRange}
                            title="No periods yet"
                            body="A period is a named date span. It does not generate daily rows."
                            actionHref={createPeriodHref}
                            actionLabel="New period"
                        />
                    ) : (
                        <div className="space-y-3">
                            {visiblePeriods.map((period) => {
                                const editHref = periodEditHref?.(period);

                                return (
                                    <article
                                        key={period.id}
                                        className="bg-card flex flex-col gap-3 rounded-2xl border p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {period.name}
                                                </p>
                                                <p className="text-muted-foreground mt-0.5 text-sm">
                                                    {formatDayRange(
                                                        period.start_date,
                                                        period.end_date,
                                                    )}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    period.contains_today
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {periodBadge(period)}
                                            </Badge>
                                        </div>
                                        <ShiftPeriodSpan
                                            startDate={period.start_date}
                                            endDate={period.end_date}
                                            containsToday={
                                                period.contains_today
                                            }
                                            daysElapsed={period.days_elapsed}
                                            daysTotal={period.days_total}
                                        />
                                        <div className="flex items-center justify-between gap-2 text-xs">
                                            <p className="text-muted-foreground">
                                                {period.branch_name ??
                                                    period.owner_type_label}
                                                {' · '}
                                                {period.scheduled_count ?? 0}{' '}
                                                assigned
                                            </p>
                                            {editHref ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link href={editHref}>
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </div>
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
    const live = windowCoversNow(
        template.start_time,
        template.end_time,
        template.is_overnight,
        now,
    );

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
                    {template.is_overnight ? (
                        <p className="text-muted-foreground mt-0.5 text-xs">
                            Overnight wrap
                        </p>
                    ) : null}
                </div>
                <div className="flex flex-col items-end gap-1.5">
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
                overnight={template.is_overnight}
            />
            <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
                <p className="text-muted-foreground">
                    {template.branch_name ?? template.owner_type_label}
                    {' · '}
                    {durationLabel(
                        template.start_time,
                        template.end_time,
                        template.is_overnight,
                    )}
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

function periodBadge(period: ShiftPeriodOption): string {
    if (!period.is_active) {
        return 'Inactive';
    }

    if (period.contains_today) {
        return 'Current';
    }

    if (period.end_date && period.end_date < todayIso()) {
        return 'Closed';
    }

    return 'Upcoming';
}

function todayIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
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
