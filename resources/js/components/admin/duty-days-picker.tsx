import { cn } from '@/lib/utils';

export const ALL_DUTY_DAYS = [1, 2, 3, 4, 5, 6, 7] as const;
export const WEEKDAY_DUTY_DAYS = [1, 2, 3, 4, 5] as const;

const DAY_OPTIONS = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
] as const;

function uniqueSorted(days: number[]): number[] {
    return [...new Set(days)]
        .filter((day) => day >= 1 && day <= 7)
        .sort((left, right) => left - right);
}

function sameDays(left: number[], right: readonly number[]): boolean {
    const a = uniqueSorted(left);
    const b = uniqueSorted([...right]);

    return a.length === b.length && a.every((day, index) => day === b[index]);
}

type DutyDaysPickerProps = {
    value: number[];
    onChange: (days: number[]) => void;
    disabled?: boolean;
};

export function DutyDaysPicker({
    value,
    onChange,
    disabled = false,
}: DutyDaysPickerProps) {
    const days = uniqueSorted(value.length > 0 ? value : [...ALL_DUTY_DAYS]);
    const allSelected = sameDays(days, ALL_DUTY_DAYS);
    const weekdaysSelected = sameDays(days, WEEKDAY_DUTY_DAYS);

    const toggleDay = (day: number): void => {
        if (disabled) {
            return;
        }

        if (days.includes(day)) {
            if (days.length === 1) {
                return;
            }

            onChange(days.filter((item) => item !== day));

            return;
        }

        onChange(uniqueSorted([...days, day]));
    };

    return (
        <div className="flex flex-col gap-2">
            <div className="flex flex-wrap gap-1.5">
                <button
                    type="button"
                    disabled={disabled}
                    aria-pressed={allSelected}
                    onClick={() => onChange([...ALL_DUTY_DAYS])}
                    className={cn(
                        'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                        allSelected
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-border/80 bg-card text-muted-foreground hover:bg-muted/40',
                    )}
                >
                    All days
                </button>
                <button
                    type="button"
                    disabled={disabled}
                    aria-pressed={weekdaysSelected}
                    onClick={() => onChange([...WEEKDAY_DUTY_DAYS])}
                    className={cn(
                        'rounded-full border px-2.5 py-1 text-xs font-medium transition-colors',
                        weekdaysSelected
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-border/80 bg-card text-muted-foreground hover:bg-muted/40',
                    )}
                >
                    Weekdays
                </button>
            </div>
            <div
                role="group"
                aria-label="Days of week"
                className="flex flex-wrap gap-1.5"
            >
                {DAY_OPTIONS.map((option) => {
                    const selected = days.includes(option.value);

                    return (
                        <button
                            key={option.value}
                            type="button"
                            disabled={disabled}
                            aria-pressed={selected}
                            onClick={() => toggleDay(option.value)}
                            className={cn(
                                'min-w-10 rounded-full border px-2.5 py-1.5 text-xs font-medium transition-colors',
                                selected
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border/80 bg-card text-muted-foreground hover:bg-muted/40',
                            )}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
