import {
    addDays,
    addMonths,
    eachDayOfInterval,
    format,
    parseISO,
    setMonth,
    setYear,
} from 'date-fns';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ShiftCalendarDay } from '@/components/admin/shift-calendar-day';
import type {
    ShiftAssignmentSelectionAction,
    ShiftAssignmentSelectionState,
} from '@/components/admin/shift-assignment-selection';
import { ShiftSelectionModeToggle } from '@/components/admin/shift-selection-mode-toggle';
import type { ShiftAssignmentCalendarPayload } from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTHS = Array.from({ length: 12 }, (_, index) => ({
    value: String(index),
    label: format(new Date(2020, index, 1), 'MMMM'),
}));

export function ShiftMonthCalendar({
    calendar,
    selection,
    allowMultiplePerDay,
    dispatch,
    onMonthChange,
}: {
    calendar: ShiftAssignmentCalendarPayload;
    selection: ShiftAssignmentSelectionState;
    allowMultiplePerDay: boolean;
    dispatch: React.Dispatch<ShiftAssignmentSelectionAction>;
    onMonthChange: (month: string) => void;
}) {
    const [selectionNotice, setSelectionNotice] = useState<string | null>(null);
    const monthDate = parseISO(`${calendar.month}-01`);
    const gridDates = useMemo(
        () =>
            eachDayOfInterval({
                start: parseISO(calendar.grid_start),
                end: parseISO(calendar.grid_end),
            }),
        [calendar.grid_end, calendar.grid_start],
    );
    const years = useMemo(() => {
        const endYear = new Date().getFullYear() + 2;

        return Array.from({ length: endYear - 2020 + 1 }, (_, index) =>
            String(2020 + index),
        );
    }, []);

    const navigateTo = (date: Date): void => {
        onMonthChange(format(date, 'yyyy-MM'));
    };

    const selectDate = (
        date: string,
        event: { ctrlKey: boolean; metaKey: boolean },
    ): void => {
        setSelectionNotice(null);

        if (selection.assignDates.has(date)) {
            dispatch({ type: 'toggle-assign-date', date });

            return;
        }

        const manualOverride = event.ctrlKey || event.metaKey;

        if (
            selection.selectionMode === 'range' &&
            !manualOverride &&
            selection.rangeAnchor !== null
        ) {
            const anchor = parseISO(selection.rangeAnchor);
            const target = parseISO(date);
            const start = anchor <= target ? anchor : target;
            const end = anchor <= target ? target : anchor;
            const candidates = eachDayOfInterval({ start, end }).map((day) =>
                format(day, 'yyyy-MM-dd'),
            );
            const eligible = candidates.filter(
                (candidate) =>
                    candidate >= calendar.today &&
                    (calendar.days[candidate]?.length ?? 0) === 0,
            );
            const skipped = candidates.length - eligible.length;

            dispatch({ type: 'add-assign-dates', dates: eligible });
            setSelectionNotice(
                skipped > 0
                    ? `${eligible.length} dates selected; ${skipped} past or assigned dates skipped.`
                    : `${eligible.length} dates selected.`,
            );

            return;
        }

        const shifts = calendar.days[date] ?? [];
        const editableShifts = shifts.filter((shift) => shift.editable);

        if (shifts.length > 0) {
            if (editableShifts.length === 1 && shifts.length === 1) {
                dispatch({
                    type: 'toggle-replace-day',
                    dayId: editableShifts[0].id,
                });
            } else if (editableShifts.length > 0) {
                setSelectionNotice(
                    'This date has multiple shifts. Use the menu on the specific shift you want to change.',
                );
            } else {
                setSelectionNotice(
                    'This shift is read-only in this workspace.',
                );
            }

            return;
        }

        if (selection.selectionMode === 'manual' || manualOverride) {
            dispatch({ type: 'toggle-assign-date', date });

            return;
        }

        if (selection.rangeAnchor === null) {
            dispatch({ type: 'set-range-anchor', date });
            setSelectionNotice('Choose the other end of the date range.');

            return;
        }
    };

    const handleKeyboardNavigation = (
        date: Date,
        event: React.KeyboardEvent<HTMLElement>,
    ): void => {
        const offsets: Record<string, number> = {
            ArrowLeft: -1,
            ArrowRight: 1,
            ArrowUp: -7,
            ArrowDown: 7,
        };
        const offset = offsets[event.key];

        if (offset === undefined) {
            return;
        }

        event.preventDefault();
        const target = format(addDays(date, offset), 'yyyy-MM-dd');
        const cell = document.querySelector<HTMLElement>(
            `[data-shift-calendar-date="${target}"]`,
        );

        cell?.focus();
    };

    return (
        <section className="border-border/80 bg-card rounded-2xl border p-3 shadow-sm sm:p-6">
            <div className="mb-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 className="text-lg font-semibold">Schedule calendar</h2>
                    <p className="text-muted-foreground text-sm">
                        Select dates, then save once to apply every staged
                        change.
                    </p>
                </div>
                <ShiftSelectionModeToggle
                    mode={selection.selectionMode}
                    onChange={(mode) => dispatch({ type: 'set-mode', mode })}
                />
            </div>

            <div className="mb-4 flex items-center justify-between gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    aria-label="Previous month"
                    onClick={() => navigateTo(addMonths(monthDate, -1))}
                >
                    <ChevronLeft />
                </Button>
                <div className="flex min-w-0 items-center gap-2">
                    <Select
                        value={String(monthDate.getMonth())}
                        onValueChange={(value) =>
                            navigateTo(setMonth(monthDate, Number(value)))
                        }
                    >
                        <SelectTrigger aria-label="Month" className="w-36">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {MONTHS.map((month) => (
                                <SelectItem
                                    key={month.value}
                                    value={month.value}
                                >
                                    {month.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(monthDate.getFullYear())}
                        onValueChange={(value) =>
                            navigateTo(setYear(monthDate, Number(value)))
                        }
                    >
                        <SelectTrigger aria-label="Year" className="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((year) => (
                                <SelectItem key={year} value={year}>
                                    {year}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    aria-label="Next month"
                    onClick={() => navigateTo(addMonths(monthDate, 1))}
                >
                    <ChevronRight />
                </Button>
            </div>

            <div className="grid grid-cols-7 gap-1 sm:gap-2">
                {WEEKDAYS.map((weekday) => (
                    <div
                        key={weekday}
                        className="text-muted-foreground py-1 text-center text-xs font-semibold sm:text-sm"
                    >
                        {weekday}
                    </div>
                ))}
                {gridDates.map((day) => {
                    const date = format(day, 'yyyy-MM-dd');

                    return (
                        <ShiftCalendarDay
                            key={date}
                            date={date}
                            dayNumber={format(day, 'd')}
                            inCurrentMonth={
                                format(day, 'yyyy-MM') === calendar.month
                            }
                            today={calendar.today}
                            shifts={calendar.days[date] ?? []}
                            selection={selection}
                            allowMultiplePerDay={allowMultiplePerDay}
                            onSelect={(event) => selectDate(date, event)}
                            onAddAnother={() =>
                                dispatch({ type: 'toggle-assign-date', date })
                            }
                            onReplace={(dayId) =>
                                dispatch({ type: 'toggle-replace-day', dayId })
                            }
                            onRemove={(dayId) =>
                                dispatch({ type: 'toggle-remove-day', dayId })
                            }
                            onKeyboardNavigate={(event) =>
                                handleKeyboardNavigation(day, event)
                            }
                        />
                    );
                })}
            </div>

            <p
                className="text-muted-foreground mt-3 min-h-5 text-sm"
                aria-live="polite"
            >
                {selectionNotice ??
                    (selection.selectionMode === 'range'
                        ? 'Click any empty day to start or finish a range. Ctrl/Cmd-click toggles one date. Amber = unsaved new dates.'
                        : 'Click any day to select it. Amber chips are unsaved; solid red chips are already assigned. Use ⋯ to change or remove.')}
            </p>
        </section>
    );
}
