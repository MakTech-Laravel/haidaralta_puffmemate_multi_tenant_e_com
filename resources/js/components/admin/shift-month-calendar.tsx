import {
    addDays,
    addMonths,
    eachDayOfInterval,
    endOfMonth,
    format,
    parseISO,
    setMonth,
    setYear,
} from 'date-fns';
import { CalendarRange, ChevronLeft, ChevronRight, Eraser } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ShiftAssignGuideSheet } from '@/components/admin/shift-assign-guide-sheet';
import { ShiftCalendarDay } from '@/components/admin/shift-calendar-day';
import {
    hasPendingShiftChanges,
    type ShiftAssignmentSelectionAction,
    type ShiftAssignmentSelectionState,
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
    const monthEndDate = format(endOfMonth(monthDate), 'yyyy-MM-dd');
    const monthIsFullyPast = monthEndDate < calendar.today;
    const remainingMonthDates = useMemo(() => {
        if (monthIsFullyPast) {
            return [];
        }

        return eachDayOfInterval({
            start: monthDate,
            end: endOfMonth(monthDate),
        })
            .map((day) => format(day, 'yyyy-MM-dd'))
            .filter((date) => date >= calendar.today);
    }, [calendar.today, monthDate, monthIsFullyPast]);

    const navigateTo = (date: Date): void => {
        onMonthChange(format(date, 'yyyy-MM'));
    };

    const selectRemainingMonth = (): void => {
        if (monthIsFullyPast || remainingMonthDates.length === 0) {
            return;
        }

        setSelectionNotice(null);

        const eligibleAssignDates: string[] = [];
        const eligibleReplaceDayIds: number[] = [];
        let skipped = 0;
        let ambiguous = 0;

        for (const candidate of remainingMonthDates) {
            const dayShifts = calendar.days[candidate] ?? [];

            if (dayShifts.length === 0) {
                eligibleAssignDates.push(candidate);
                continue;
            }

            const dayEditable = dayShifts.filter((shift) => shift.editable);

            if (dayEditable.length === 1 && dayShifts.length === 1) {
                eligibleReplaceDayIds.push(dayEditable[0].id);
                continue;
            }

            if (dayEditable.length > 1 || dayShifts.length > 1) {
                ambiguous += 1;
                continue;
            }

            skipped += 1;
        }

        if (
            eligibleAssignDates.length === 0 &&
            eligibleReplaceDayIds.length === 0
        ) {
            setSelectionNotice(
                ambiguous > 0
                    ? 'No dates could be selected. Multi-shift days need the ⋯ menu.'
                    : 'No eligible dates left in this month.',
            );

            return;
        }

        dispatch({
            type: 'apply-range-selection',
            dates: eligibleAssignDates,
            replaceDayIds: eligibleReplaceDayIds,
        });

        const parts: string[] = [];

        if (eligibleAssignDates.length > 0) {
            parts.push(
                `${eligibleAssignDates.length} new date${eligibleAssignDates.length === 1 ? '' : 's'}`,
            );
        }

        if (eligibleReplaceDayIds.length > 0) {
            parts.push(
                `${eligibleReplaceDayIds.length} assigned date${eligibleReplaceDayIds.length === 1 ? '' : 's'} to change`,
            );
        }

        if (ambiguous > 0) {
            parts.push(
                `${ambiguous} multi-shift date${ambiguous === 1 ? '' : 's'} skipped`,
            );
        }

        if (skipped > 0) {
            parts.push(
                `${skipped} read-only date${skipped === 1 ? '' : 's'} skipped`,
            );
        }

        setSelectionNotice(
            `${parts.join('; ')}. Past dates were not selected.`,
        );
    };

    const selectDate = (
        date: string,
        event: { ctrlKey: boolean; metaKey: boolean; shiftKey: boolean },
    ): void => {
        setSelectionNotice(null);

        if (selection.assignDates.has(date)) {
            dispatch({ type: 'toggle-assign-date', date });

            return;
        }

        const forceManual = event.ctrlKey || event.metaKey;
        const forceRange = event.shiftKey && !forceManual;
        const useRangeMode =
            forceRange || (!forceManual && selection.selectionMode === 'range');
        const shifts = calendar.days[date] ?? [];
        const editableShifts = shifts.filter((shift) => shift.editable);

        if (selection.rangeAnchor !== null && !forceManual) {
            const anchor = parseISO(selection.rangeAnchor);
            const target = parseISO(date);
            const start = anchor <= target ? anchor : target;
            const end = anchor <= target ? target : anchor;
            const candidates = eachDayOfInterval({ start, end }).map((day) =>
                format(day, 'yyyy-MM-dd'),
            );

            const eligibleAssignDates: string[] = [];
            const eligibleReplaceDayIds: number[] = [];
            let skipped = 0;
            let ambiguous = 0;

            for (const candidate of candidates) {
                if (candidate < calendar.today) {
                    skipped += 1;
                    continue;
                }

                const dayShifts = calendar.days[candidate] ?? [];

                if (dayShifts.length === 0) {
                    eligibleAssignDates.push(candidate);
                    continue;
                }

                const dayEditable = dayShifts.filter((shift) => shift.editable);

                if (dayEditable.length === 1 && dayShifts.length === 1) {
                    eligibleReplaceDayIds.push(dayEditable[0].id);
                    continue;
                }

                if (dayEditable.length > 1 || dayShifts.length > 1) {
                    ambiguous += 1;
                    continue;
                }

                skipped += 1;
            }

            if (
                eligibleAssignDates.length > 0 ||
                eligibleReplaceDayIds.length > 0
            ) {
                dispatch({
                    type: 'apply-range-selection',
                    dates: eligibleAssignDates,
                    replaceDayIds: eligibleReplaceDayIds,
                });
            } else {
                dispatch({ type: 'set-range-anchor', date: null });
            }

            const parts: string[] = [];

            if (eligibleAssignDates.length > 0) {
                parts.push(
                    `${eligibleAssignDates.length} new date${eligibleAssignDates.length === 1 ? '' : 's'}`,
                );
            }

            if (eligibleReplaceDayIds.length > 0) {
                parts.push(
                    `${eligibleReplaceDayIds.length} assigned date${eligibleReplaceDayIds.length === 1 ? '' : 's'} to change`,
                );
            }

            if (ambiguous > 0) {
                parts.push(
                    `${ambiguous} multi-shift date${ambiguous === 1 ? '' : 's'} skipped`,
                );
            }

            if (skipped > 0) {
                parts.push(
                    `${skipped} past or read-only date${skipped === 1 ? '' : 's'} skipped`,
                );
            }

            setSelectionNotice(
                parts.length > 0
                    ? parts.join('; ') + '.'
                    : 'No eligible dates in that range.',
            );

            return;
        }

        if (shifts.length > 0) {
            if (editableShifts.length === 0) {
                setSelectionNotice(
                    'This shift is read-only in this workspace.',
                );

                return;
            }

            if (editableShifts.length > 1 || shifts.length > 1) {
                setSelectionNotice(
                    'This date has multiple shifts. Use the menu on the specific shift you want to change.',
                );

                return;
            }

            if (!useRangeMode) {
                dispatch({
                    type: 'toggle-replace-day',
                    dayId: editableShifts[0].id,
                });

                return;
            }

            dispatch({ type: 'set-range-anchor', date });
            setSelectionNotice(
                forceRange
                    ? 'Shift-click or click the other end to change that range of assigned dates.'
                    : 'Choose the other end to change that range of assigned dates.',
            );

            return;
        }

        if (!useRangeMode) {
            dispatch({ type: 'toggle-assign-date', date });

            return;
        }

        dispatch({ type: 'set-range-anchor', date });
        setSelectionNotice(
            forceRange
                ? 'Shift-click or click the other end of the date range.'
                : 'Choose the other end of the date range.',
        );
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
                <div className="min-w-0">
                    <div className="flex items-center gap-1.5">
                        <h2 className="text-lg font-semibold">
                            Schedule calendar
                        </h2>
                        <ShiftAssignGuideSheet
                            allowMultiplePerDay={allowMultiplePerDay}
                        />
                    </div>
                    <p className="text-muted-foreground text-sm">
                        Select dates, then save once to apply every staged
                        change.
                    </p>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={monthIsFullyPast}
                        title={
                            monthIsFullyPast
                                ? 'This month is entirely in the past'
                                : 'Select every remaining day in this month'
                        }
                        onClick={selectRemainingMonth}
                    >
                        <CalendarRange />
                        Select remaining days
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={
                            !hasPendingShiftChanges(selection) &&
                            selection.rangeAnchor === null
                        }
                        title="Clear staged date selections"
                        onClick={() => {
                            dispatch({ type: 'clear-staged' });
                            setSelectionNotice(null);
                        }}
                    >
                        <Eraser />
                        Clear selection
                    </Button>
                    <ShiftSelectionModeToggle
                        mode={selection.selectionMode}
                        onChange={(mode) =>
                            dispatch({ type: 'set-mode', mode })
                        }
                    />
                </div>
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
                        ? 'Click two days for a range (empty = new, assigned = change). Ctrl/Cmd-click one date. Shift-click also starts a range.'
                        : 'Click one-by-one. Shift-click a range on empty or assigned days; Ctrl/Cmd-click keeps single select.')}
            </p>
        </section>
    );
}
