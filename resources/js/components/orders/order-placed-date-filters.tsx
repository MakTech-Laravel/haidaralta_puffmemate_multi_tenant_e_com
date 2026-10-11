import { format } from 'date-fns';
import { DatePicker } from '@/components/ui/date-picker';

function todayValue(): string {
    return format(new Date(), 'yyyy-MM-dd');
}

function clampDate(
    value: string,
    min: string | null,
    max: string | null,
): string | null {
    if (value === '') {
        return null;
    }

    let next = value;

    if (min !== null && next < min) {
        next = min;
    }

    if (max !== null && next > max) {
        next = max;
    }

    return next;
}

export function OrderPlacedDateFilters({
    from,
    to,
    onChange,
}: {
    from: string | null;
    to: string | null;
    onChange: (key: 'from' | 'to', value: string | null) => void;
}) {
    const today = todayValue();
    const fromMax = to !== null && to < today ? to : today;
    const toMin = from;

    return (
        <>
            <div className="grid w-40 gap-2">
                <label className="text-sm font-medium" htmlFor="orders-placed-from">
                    From
                </label>
                <DatePicker
                    id="orders-placed-from"
                    value={from}
                    max={fromMax}
                    placeholder="Any date"
                    onChange={(value) =>
                        onChange('from', clampDate(value, null, fromMax))
                    }
                    clearable
                />
            </div>
            <div className="grid w-40 gap-2">
                <label className="text-sm font-medium" htmlFor="orders-placed-to">
                    To
                </label>
                <DatePicker
                    id="orders-placed-to"
                    value={to}
                    min={toMin}
                    max={today}
                    placeholder="Any date"
                    onChange={(value) =>
                        onChange('to', clampDate(value, toMin, today))
                    }
                    clearable
                />
            </div>
        </>
    );
}
