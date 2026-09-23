import type { ReactNode } from 'react';
import {
    CalendarRange,
    Clock3,
    Store,
    Users,
} from 'lucide-react';
import {
    COVERAGE_SCALE_HEADER,
    ShiftCoverageScale,
} from '@/components/admin/shift-coverage-scale';
import { formatClock, timeBarSegments } from '@/lib/shift-format';
import { cn } from '@/lib/utils';

const SAMPLE_WINDOWS = [
    { name: 'Opening', start: '07:00', end: '15:00' },
    { name: 'Midday', start: '09:00', end: '17:00' },
    { name: 'Evening', start: '16:00', end: '23:00' },
] as const;

export function ShiftBranchEmptyState({
    picker,
}: {
    picker: ReactNode;
}) {
    return (
        <section className="bg-card grid w-full overflow-hidden rounded-2xl border lg:grid-cols-[minmax(18rem,22rem)_minmax(0,1fr)]">
            <div className="flex flex-col gap-5 p-5 sm:p-6">
                <div className="flex items-start gap-3">
                    <div className="bg-primary text-primary-foreground flex size-10 shrink-0 items-center justify-center rounded-xl">
                        <Store className="size-5" />
                    </div>
                    <div className="min-w-0 space-y-1">
                        <h2 className="text-lg font-semibold tracking-tight">
                            Select a store
                        </h2>
                        <p className="text-muted-foreground text-sm leading-relaxed">
                            Open one branch at a time. Coverage, on-duty, and
                            that store&apos;s shifts load next.
                        </p>
                    </div>
                </div>

                <div className="w-full">{picker}</div>

                <ul className="grid gap-2">
                    {WHAT_YOU_GET.map((item) => (
                        <li
                            key={item.label}
                            className="flex items-center gap-2.5 text-sm"
                        >
                            <item.icon className="text-muted-foreground size-4 shrink-0" />
                            <span>
                                <span className="font-medium">{item.label}</span>
                                <span className="text-muted-foreground">
                                    {' · '}
                                    {item.hint}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="border-t lg:border-t-0 lg:border-l">
                <CoveragePreview />
                <ol className="grid gap-px border-t sm:grid-cols-3">
                    {HOW_IT_WORKS.map((step, index) => (
                        <li
                            key={step.title}
                            className="bg-muted/20 flex gap-2.5 px-4 py-3"
                        >
                            <span className="text-muted-foreground mt-0.5 text-[11px] font-semibold tabular-nums">
                                {index + 1}
                            </span>
                            <div className="min-w-0">
                                <p className="text-sm font-medium">
                                    {step.title}
                                </p>
                                <p className="text-muted-foreground text-xs leading-relaxed">
                                    {step.hint}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}

const WHAT_YOU_GET = [
    {
        icon: Users,
        label: 'Staff & drivers on duty',
        hint: 'Separate live counts by type',
    },
    {
        icon: Clock3,
        label: 'Shifts',
        hint: 'Named start and end times',
    },
    {
        icon: CalendarRange,
        label: 'Assigned people',
        hint: 'Who has a shift and on which dates',
    },
] as const;

const HOW_IT_WORKS = [
    {
        title: 'Choose a branch',
        hint: 'Only that store loads.',
    },
    {
        title: 'See who is on duty',
        hint: 'Live windows for today.',
    },
    {
        title: 'Assign people',
        hint: 'Each person has their own dates.',
    },
] as const;

function CoveragePreview() {
    const now = new Date();

    return (
        <div className="flex flex-col">
            <header className="flex items-end justify-between gap-3 px-4 py-3">
                <div>
                    <p className="text-sm font-semibold tracking-tight">
                        Today&apos;s coverage
                    </p>
                    <p className="text-muted-foreground text-xs">
                        Midnight to midnight. Real names appear after you pick
                        a store.
                    </p>
                </div>
                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    Sample day
                </p>
            </header>

            <div className="overflow-x-auto border-t">
                <div className="flex min-w-[56rem]">
                    <div className="border-border/80 w-28 shrink-0 border-r sm:w-36">
                        <div
                            className={cn(
                                'text-muted-foreground flex items-end px-3 pb-1 text-[10px] font-medium tracking-wide uppercase',
                                COVERAGE_SCALE_HEADER,
                            )}
                        >
                            Window
                        </div>
                        {SAMPLE_WINDOWS.map((window) => (
                            <div
                                key={window.name}
                                className="flex h-11 flex-col justify-center gap-0.5 border-t px-3"
                            >
                                <p className="truncate text-sm font-medium">
                                    {window.name}
                                </p>
                                <p className="text-muted-foreground truncate text-[11px] tabular-nums">
                                    {formatClock(window.start)} –{' '}
                                    {formatClock(window.end)}
                                </p>
                            </div>
                        ))}
                    </div>

                    <ShiftCoverageScale now={now}>
                        {SAMPLE_WINDOWS.map((window) => {
                            const segments = timeBarSegments(
                                window.start,
                                window.end,
                            );

                            return (
                                <div
                                    key={window.name}
                                    className="relative h-11 border-t"
                                >
                                    {segments.map((segment, index) => (
                                        <span
                                            key={`${window.name}-${index}`}
                                            className="bg-primary/70 absolute top-2.5 bottom-2.5 rounded-md"
                                            style={{
                                                left: `${segment.startPct}%`,
                                                width: `${Math.max(segment.widthPct, 1.2)}%`,
                                            }}
                                        />
                                    ))}
                                </div>
                            );
                        })}
                    </ShiftCoverageScale>
                </div>
            </div>
        </div>
    );
}
