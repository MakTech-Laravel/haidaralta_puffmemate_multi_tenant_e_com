import type { ShiftTemplateOption } from '@/components/admin/workforce-types';
import {
    COVERAGE_SCALE_HEADER,
    ShiftCoverageScale,
} from '@/components/admin/shift-coverage-scale';
import {
    formatClock,
    timeBarSegments,
    windowCoversNow,
} from '@/lib/shift-format';
import { cn } from '@/lib/utils';

export function ShiftCoverageMap({
    templates,
    now,
    selectedTemplateId = null,
    onSelectTemplate,
}: {
    templates: ShiftTemplateOption[];
    now: Date;
    selectedTemplateId?: number | null;
    onSelectTemplate?: (templateId: number | null) => void;
}) {
    const lanes = uniqueWindows(
        templates.filter(
            (template) => template.start_time && template.end_time,
        ),
    );

    if (lanes.length === 0) {
        return null;
    }

    const selectable = Boolean(onSelectTemplate);
    const filtered = selectedTemplateId !== null;

    return (
        <section className="bg-card overflow-hidden rounded-2xl border">
            <header className="flex flex-wrap items-end justify-between gap-3 border-b px-5 py-4">
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        Today&apos;s coverage
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Full day, 00:00 to 24:00. Red is on now.
                        {selectable
                            ? ' Pick a window, or filter people below.'
                            : null}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {filtered ? (
                        <button
                            type="button"
                            className="text-muted-foreground hover:text-foreground text-xs font-medium"
                            onClick={() => onSelectTemplate?.(null)}
                        >
                            All shifts
                        </button>
                    ) : null}
                    <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                        {lanes.length} {lanes.length === 1 ? 'window' : 'windows'}
                    </p>
                </div>
            </header>

            <div className="flex min-w-0">
                <div className="border-border/80 w-32 shrink-0 border-r sm:w-40">
                    <div
                        className={cn(
                            'text-muted-foreground flex items-end px-3 pb-1 text-[11px] font-medium tracking-wide uppercase',
                            COVERAGE_SCALE_HEADER,
                        )}
                    >
                        Window
                    </div>
                    {lanes.map((template) => {
                        const active =
                            selectedTemplateId === null ||
                            selectedTemplateId === template.id;

                        return (
                            <LaneLabel
                                key={template.id}
                                template={template}
                                active={active}
                                selectable={selectable}
                                onSelect={() =>
                                    onSelectTemplate?.(
                                        selectedTemplateId === template.id
                                            ? null
                                            : template.id,
                                    )
                                }
                            />
                        );
                    })}
                </div>

                <ShiftCoverageScale now={now}>
                    {lanes.map((template) => {
                        const live = windowCoversNow(
                            template.start_time,
                            template.end_time,
                            now,
                        );
                        const active =
                            selectedTemplateId === null ||
                            selectedTemplateId === template.id;
                        const segments = timeBarSegments(
                            template.start_time,
                            template.end_time,
                        );

                        return (
                            <button
                                key={template.id}
                                type="button"
                                disabled={!selectable}
                                onClick={() =>
                                    onSelectTemplate?.(
                                        selectedTemplateId === template.id
                                            ? null
                                            : template.id,
                                    )
                                }
                                className={cn(
                                    'relative block h-11 w-full border-t text-left transition-opacity',
                                    !active && 'opacity-35',
                                    selectable && 'hover:bg-muted/40',
                                )}
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
                                        {segment.widthPct > 8 ? (
                                            <span className="truncate">
                                                {formatClock(
                                                    template.start_time,
                                                )}
                                                {' – '}
                                                {formatClock(template.end_time)}
                                            </span>
                                        ) : null}
                                    </span>
                                ))}
                            </button>
                        );
                    })}
                </ShiftCoverageScale>
            </div>
        </section>
    );
}

function LaneLabel({
    template,
    active,
    selectable,
    onSelect,
}: {
    template: ShiftTemplateOption;
    active: boolean;
    selectable: boolean;
    onSelect: () => void;
}) {
    const className = cn(
        'flex h-11 w-full flex-col justify-center gap-0.5 border-t px-3 text-left transition-opacity',
        !active && 'opacity-35',
        selectable && 'hover:bg-muted/40',
    );

    const body = (
        <>
            <p className="truncate text-sm font-medium">{template.name}</p>
            <p className="text-muted-foreground truncate text-[11px] tabular-nums">
                {formatClock(template.start_time)} –{' '}
                {formatClock(template.end_time)}
            </p>
        </>
    );

    if (!selectable) {
        return <div className={className}>{body}</div>;
    }

    return (
        <button type="button" className={className} onClick={onSelect}>
            {body}
        </button>
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
