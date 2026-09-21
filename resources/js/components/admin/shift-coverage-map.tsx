import type { ShiftTemplateOption } from '@/components/admin/workforce-types';
import {
    formatClock,
    hourTickLabel,
    nowMarkerPct,
    timeBarSegments,
    windowCoversNow,
} from '@/lib/shift-format';
import { cn } from '@/lib/utils';

const HOURS = 24;
const HOUR_TICKS = Array.from({ length: HOURS + 1 }, (_, hour) => hour);

export function ShiftCoverageMap({
    templates,
    now,
}: {
    templates: ShiftTemplateOption[];
    now: Date;
}) {
    const lanes = uniqueWindows(
        templates.filter(
            (template) => template.start_time && template.end_time,
        ),
    );

    if (lanes.length === 0) {
        return null;
    }

    const nowPct = nowMarkerPct(now);
    const nowLabel = now.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });

    return (
        <section className="bg-card overflow-hidden rounded-2xl border">
            <header className="flex flex-wrap items-end justify-between gap-3 border-b px-5 py-4">
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        Today&apos;s coverage
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Full day, midnight to midnight. Red is on now.
                    </p>
                </div>
                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                    {lanes.length}{' '}
                    {lanes.length === 1 ? 'window' : 'windows'}
                </p>
            </header>

            <div className="flex min-w-0">
                <div className="border-border/80 w-36 shrink-0 border-r sm:w-44">
                    <div className="text-muted-foreground flex h-12 items-end px-4 pb-2 text-[11px] font-medium tracking-wide uppercase">
                        Window
                    </div>
                    {lanes.map((template) => (
                        <div
                            key={template.id}
                            className="flex h-11 flex-col justify-center gap-0.5 border-t px-4"
                        >
                            <p className="truncate text-sm font-medium">
                                {template.name}
                            </p>
                            <p className="text-muted-foreground truncate text-[11px] tabular-nums">
                                {formatClock(template.start_time)} –{' '}
                                {formatClock(template.end_time)}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="min-w-0 flex-1 overflow-x-auto">
                    <div className="relative min-w-[48rem]">
                        <div className="text-muted-foreground/80 flex h-5 pt-2 text-[10px] font-medium tracking-wide uppercase">
                            <span className="w-1/2 pl-2">Morning</span>
                            <span className="w-1/2 pl-2">
                                Afternoon / night
                            </span>
                        </div>
                        <div className="relative h-7">
                            {HOUR_TICKS.map((hour) => (
                                <span
                                    key={hour}
                                    className={cn(
                                        'text-muted-foreground absolute bottom-1 text-[10px] font-medium tabular-nums',
                                        hour === 0
                                            ? 'left-0'
                                            : hour === 24
                                              ? 'right-0'
                                              : '-translate-x-1/2',
                                        (hour === 0 ||
                                            hour === 12 ||
                                            hour === 24) &&
                                            'text-foreground',
                                    )}
                                    style={
                                        hour === 0 || hour === 24
                                            ? undefined
                                            : {
                                                  left: `${(hour / HOURS) * 100}%`,
                                              }
                                    }
                                >
                                    {hourTickLabel(hour)}
                                </span>
                            ))}
                        </div>

                        <div
                            className="bg-muted/35 pointer-events-none absolute top-12 bottom-0 w-1/2"
                            aria-hidden
                        />

                        <div className="relative">
                            {HOUR_TICKS.map((hour) => (
                                <span
                                    key={`line-${hour}`}
                                    className={cn(
                                        'pointer-events-none absolute top-0 bottom-0 w-px',
                                        hour === 0 || hour === 24
                                            ? 'bg-border'
                                            : hour === 12
                                              ? 'bg-border/80'
                                              : 'bg-border/35',
                                    )}
                                    style={{
                                        left: `${(hour / HOURS) * 100}%`,
                                    }}
                                />
                            ))}

                            <span
                                className="bg-primary pointer-events-none absolute top-0 bottom-0 z-20 w-0.5"
                                style={{ left: `${nowPct}%` }}
                            >
                                <span className="bg-primary text-primary-foreground absolute top-1 left-1/2 z-30 -translate-x-1/2 rounded-full px-1.5 py-px text-[10px] font-semibold whitespace-nowrap tabular-nums shadow-sm">
                                    Now {nowLabel}
                                </span>
                            </span>

                            {lanes.map((template) => {
                                const live = windowCoversNow(
                                    template.start_time,
                                    template.end_time,
                                    template.is_overnight,
                                    now,
                                );
                                const segments = timeBarSegments(
                                    template.start_time,
                                    template.end_time,
                                    template.is_overnight,
                                );

                                return (
                                    <div
                                        key={template.id}
                                        className="relative h-11 border-t"
                                    >
                                        {segments.map((segment, index) => (
                                            <span
                                                key={`${template.id}-${index}`}
                                                className={cn(
                                                    'absolute top-2.5 bottom-2.5 flex items-center overflow-hidden rounded-md px-2 text-[11px] font-medium text-white shadow-sm',
                                                    live
                                                        ? 'bg-primary'
                                                        : 'bg-primary/55',
                                                )}
                                                style={{
                                                    left: `${segment.startPct}%`,
                                                    width: `${Math.max(segment.widthPct, 1.2)}%`,
                                                }}
                                            >
                                                {segment.widthPct > 10 ? (
                                                    <span className="truncate">
                                                        {formatClock(
                                                            template.start_time,
                                                        )}
                                                        {template.is_overnight &&
                                                        index ===
                                                            segments.length -
                                                                1
                                                            ? ' next day'
                                                            : ''}
                                                    </span>
                                                ) : null}
                                            </span>
                                        ))}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function uniqueWindows(
    templates: ShiftTemplateOption[],
): ShiftTemplateOption[] {
    const seen = new Set<string>();
    const unique: ShiftTemplateOption[] = [];

    for (const template of templates) {
        const key = `${template.name}|${template.start_time}|${template.end_time}`;

        if (seen.has(key)) {
            continue;
        }

        seen.add(key);
        unique.push(template);
    }

    return unique;
}
