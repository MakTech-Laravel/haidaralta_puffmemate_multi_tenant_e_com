import { cn } from '@/lib/utils';
import {
    nowMarkerPct,
    timeBarSegments,
} from '@/lib/shift-format';

const HOUR_TICKS = [0, 6, 12, 18, 24];

export function ShiftTimeBar({
    start,
    end,
    showNow = true,
    className,
}: {
    start: string | null | undefined;
    end: string | null | undefined;
    showNow?: boolean;
    className?: string;
}) {
    const segments = timeBarSegments(start, end);
    const nowPct = nowMarkerPct();

    return (
        <div className={cn('space-y-1.5', className)}>
            <div className="bg-muted relative h-2.5 overflow-hidden rounded-full">
                {segments.map((segment, index) => (
                    <span
                        key={`${segment.startPct}-${index}`}
                        className="bg-primary absolute inset-y-0 rounded-full"
                        style={{
                            left: `${segment.startPct}%`,
                            width: `${Math.max(segment.widthPct, 1.2)}%`,
                        }}
                    />
                ))}
                {showNow ? (
                    <span
                        className="bg-foreground absolute top-[-3px] h-4 w-px"
                        style={{ left: `${nowPct}%` }}
                    />
                ) : null}
            </div>
            <div className="text-muted-foreground flex justify-between text-[10px] font-medium tracking-wide uppercase">
                {HOUR_TICKS.map((hour) => (
                    <span key={hour}>{hour === 24 ? '24' : hour}</span>
                ))}
            </div>
        </div>
    );
}
