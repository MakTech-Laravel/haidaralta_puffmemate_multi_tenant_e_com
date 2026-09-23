import { Plus } from 'lucide-react';
import { ShiftDayActions } from '@/components/admin/shift-day-actions';
import type { ShiftAssignmentSelectionState } from '@/components/admin/shift-assignment-selection';
import type { ShiftAssignmentCalendarDay } from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { formatAppDate } from '@/lib/datetime';
import { cn } from '@/lib/utils';

export function ShiftCalendarDay({
    date,
    dayNumber,
    inCurrentMonth,
    today,
    shifts,
    selection,
    allowMultiplePerDay,
    onSelect,
    onAddAnother,
    onReplace,
    onRemove,
    onKeyboardNavigate,
}: {
    date: string;
    dayNumber: string;
    inCurrentMonth: boolean;
    today: string;
    shifts: ShiftAssignmentCalendarDay[];
    selection: ShiftAssignmentSelectionState;
    allowMultiplePerDay: boolean;
    onSelect: (event: {
        ctrlKey: boolean;
        metaKey: boolean;
        shiftKey: boolean;
    }) => void;
    onAddAnother: () => void;
    onReplace: (dayId: number) => void;
    onRemove: (dayId: number) => void;
    onKeyboardNavigate: (event: React.KeyboardEvent<HTMLElement>) => void;
}) {
    const isPast = date < today;
    const isToday = date === today;
    const isPendingAssignment = selection.assignDates.has(date);
    const hasPendingDayChange = shifts.some(
        (shift) =>
            selection.replaceDayIds.has(shift.id) ||
            selection.removeDayIds.has(shift.id),
    );
    const hasAssignedShift = shifts.length > 0;
    const visibleShifts = shifts.slice(0, 2);
    const hiddenShiftCount = Math.max(0, shifts.length - visibleShifts.length);
    const selectable = !isPast;

    const handleKeyDown = (event: React.KeyboardEvent<HTMLElement>): void => {
        onKeyboardNavigate(event);

        if (!selectable) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onSelect(event);
        }
    };

    return (
        <div
            role="button"
            tabIndex={selectable ? 0 : -1}
            data-shift-calendar-date={date}
            aria-disabled={!selectable}
            aria-label={`${formatAppDate(date)}${isToday ? ', today' : ''}${isPendingAssignment ? ', unsaved new shift' : ''}`}
            aria-pressed={isPendingAssignment}
            onClick={(event) => {
                if (!selectable) {
                    return;
                }

                onSelect(event);
            }}
            onKeyDown={handleKeyDown}
            className={cn(
                'border-border/70 bg-background relative flex min-h-24 flex-col rounded-lg border p-1.5 text-left transition-colors sm:min-h-32 sm:p-2',
                selectable && 'hover:border-primary/40 cursor-pointer',
                !selectable && 'cursor-not-allowed',
                !inCurrentMonth &&
                    !isPendingAssignment &&
                    'bg-muted/30 text-muted-foreground',
                isPast &&
                    !hasAssignedShift &&
                    'bg-muted/60 text-muted-foreground',
                isPast && hasAssignedShift && 'opacity-60',
                hasAssignedShift &&
                    !isPast &&
                    !isPendingAssignment &&
                    !hasPendingDayChange &&
                    'border-primary/30 bg-primary/5',
                isPendingAssignment &&
                    'border-amber-500 bg-amber-50 ring-2 ring-amber-400/60',
                hasPendingDayChange &&
                    !isPendingAssignment &&
                    'border-sky-500 bg-sky-50 ring-2 ring-sky-400/50',
                selection.rangeAnchor === date &&
                    !isPendingAssignment &&
                    'border-amber-500 ring-2 ring-amber-500',
                isToday &&
                    !isPendingAssignment &&
                    !hasPendingDayChange &&
                    'ring-ring ring-2 ring-offset-1',
                'focus-visible:ring-ring outline-none focus-visible:ring-2',
            )}
        >
            <span
                className={cn(
                    'mb-1 flex size-7 items-center justify-center rounded-md text-sm font-semibold sm:size-8',
                    isPendingAssignment && 'bg-amber-500 text-white',
                    hasPendingDayChange &&
                        !isPendingAssignment &&
                        'bg-sky-600 text-white',
                )}
            >
                {dayNumber}
            </span>

            <div className="min-h-0 flex-1 space-y-1">
                {isPendingAssignment ? (
                    <div className="rounded-md border-2 border-dashed border-amber-600 bg-amber-100 px-1.5 py-1 text-[10px] font-semibold text-amber-950 sm:text-xs">
                        <span className="block">New shift</span>
                        <span className="block font-medium opacity-80">
                            Unsaved
                        </span>
                    </div>
                ) : null}

                {visibleShifts.map((shift) => (
                    <ShiftDayActions
                        key={shift.id}
                        shift={shift}
                        replacing={selection.replaceDayIds.has(shift.id)}
                        removing={selection.removeDayIds.has(shift.id)}
                        onReplace={() => onReplace(shift.id)}
                        onRemove={() => onRemove(shift.id)}
                    />
                ))}

                {hiddenShiftCount > 0 ? (
                    <p className="text-muted-foreground px-1 text-[10px] sm:text-xs">
                        +{hiddenShiftCount} more
                    </p>
                ) : null}

                {allowMultiplePerDay &&
                !isPast &&
                hasAssignedShift &&
                !isPendingAssignment ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-primary h-6 w-full justify-start px-1 text-[10px] sm:text-xs"
                        onClick={(event) => {
                            event.stopPropagation();
                            onAddAnother();
                        }}
                    >
                        <Plus className="size-3" />
                        Add another
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
