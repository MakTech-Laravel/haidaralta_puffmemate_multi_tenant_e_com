import type { ReactNode } from 'react';
import {
    formatLiveClock,
    hourTickLabel,
    nowMarkerPct,
} from '@/lib/shift-format';
import { cn } from '@/lib/utils';

export const COVERAGE_HOURS = 24;
export const COVERAGE_LABELS = Array.from(
    { length: COVERAGE_HOURS + 1 },
    (_, hour) => hour,
);
export const COVERAGE_SCALE_HEADER = 'h-[3.75rem]';

export function ShiftCoverageScale({
    now,
    showNow = true,
    children,
}: {
    now?: Date;
    showNow?: boolean;
    children: ReactNode;
}) {
    const nowPct = now ? nowMarkerPct(now) : null;
    const nowLabel = now ? formatLiveClock(now) : null;

    return (
        <div className="min-w-0 flex-1">
            <div className="relative">
                <div className="relative h-5">
                    {showNow && nowPct !== null && nowLabel ? (
                        <span
                            className="bg-primary text-primary-foreground absolute top-0.5 z-20 rounded-full px-1.5 py-px text-[10px] font-semibold tabular-nums"
                            style={{
                                left: `${nowPct}%`,
                                transform: nowMarkerTransform(nowPct),
                            }}
                        >
                            Now {nowLabel}
                        </span>
                    ) : null}
                </div>
                <div className="text-muted-foreground/80 flex h-5 text-[10px] font-medium tracking-wide uppercase">
                    <span className="w-1/2 self-end pb-0.5 pl-1.5">Morning</span>
                    <span className="w-1/2 self-end pb-0.5 pl-1.5">
                        Afternoon / night
                    </span>
                </div>
                <div className="relative h-5">
                    {COVERAGE_LABELS.map((hour) => (
                        <span
                            key={hour}
                            className={cn(
                                'absolute bottom-0.5 text-[10px] font-medium tabular-nums',
                                hour === 0 || hour === 12 || hour === 24
                                    ? 'text-foreground'
                                    : 'text-muted-foreground',
                            )}
                            style={{
                                left: `${(hour / COVERAGE_HOURS) * 100}%`,
                                transform: hourLabelTransform(hour),
                            }}
                        >
                            {hourTickLabel(hour)}
                        </span>
                    ))}
                </div>
            </div>

            <div className="relative border-t">
                <div
                    className="pointer-events-none absolute inset-0 grid"
                    style={{
                        gridTemplateColumns: `repeat(${COVERAGE_HOURS}, minmax(0, 1fr))`,
                    }}
                    aria-hidden
                >
                    {Array.from({ length: COVERAGE_HOURS }, (_, hour) => (
                        <div
                            key={hour}
                            className={cn(
                                'border-border/30 border-l',
                                (hour === 0 || hour === 12) && 'border-border',
                                hour >= 12 && 'bg-muted/20',
                            )}
                        />
                    ))}
                </div>
                <div
                    className="border-border pointer-events-none absolute inset-y-0 right-0 z-10 w-px"
                    aria-hidden
                />
                {nowPct !== null ? (
                    <span
                        className="bg-primary pointer-events-none absolute top-0 bottom-0 z-20 w-px"
                        style={{ left: `${nowPct}%` }}
                    />
                ) : null}
                <div className="relative z-10">{children}</div>
            </div>
        </div>
    );
}

function hourLabelTransform(hour: number): string {
    if (hour === 0) {
        return 'none';
    }

    if (hour === 24) {
        return 'translateX(-100%)';
    }

    return 'translateX(-50%)';
}

function nowMarkerTransform(pct: number): string {
    if (pct < 8) {
        return 'none';
    }

    if (pct > 92) {
        return 'translateX(-100%)';
    }

    return 'translateX(-50%)';
}
