import { cn } from '@/lib/utils';

export function ShiftPeriodSpan({
    startDate,
    endDate,
    containsToday = false,
    daysElapsed = 0,
    daysTotal = 0,
    className,
}: {
    startDate: string | null | undefined;
    endDate: string | null | undefined;
    containsToday?: boolean;
    daysElapsed?: number;
    daysTotal?: number;
    className?: string;
}) {
    const progress =
        containsToday && daysTotal > 0
            ? Math.min(100, Math.max(4, (daysElapsed / daysTotal) * 100))
            : startDate && endDate && !containsToday && endDate < todayIso()
              ? 100
              : 0;

    return (
        <div className={cn('space-y-1.5', className)}>
            <div className="bg-muted relative h-2 overflow-hidden rounded-full">
                <span
                    className={cn(
                        'absolute inset-y-0 left-0 rounded-full',
                        containsToday ? 'bg-primary' : 'bg-foreground/25',
                    )}
                    style={{ width: `${progress}%` }}
                />
            </div>
            <p className="text-muted-foreground text-[11px]">
                {containsToday
                    ? `Day ${daysElapsed} of ${daysTotal}`
                    : daysTotal > 0
                      ? `${daysTotal} days`
                      : 'Choose a start and end date'}
            </p>
        </div>
    );
}

function todayIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}
