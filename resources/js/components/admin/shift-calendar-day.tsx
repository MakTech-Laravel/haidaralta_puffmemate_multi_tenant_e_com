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
    onSelect: (event: React.MouseEvent<HTMLButtonElement>) => void;
    onAddAnother: () => void;
    onReplace: (dayId: number) => void;
    onRemove: (dayId: number) => void;
    onKeyboardNavigate: (event: React.KeyboardEvent<HTMLButtonElement>) => void;
}) {
    const isPast = date < today;
    const isToday = date === today;
    const isPendingAssignment = selection.assignDates.has(date);
    const hasAssignedShift = shifts.length > 0;
    const visibleShifts = shifts.slice(0, 2);
    const hiddenShiftCount = Math.max(0, shifts.length - visibleShifts.length);

    return (
        <div
            className={cn(
                'border-border/70 bg-background relative min-h-24 rounded-lg border p-1.5 transition-colors sm:min-h-32 sm:p-2',
                !inCurrentMonth && 'bg-muted/30 text-muted-foreground',
                isPast &&
                    !hasAssignedShift &&
                    'bg-muted/60 text-muted-foreground',
                isPast && hasAssignedShift && 'opacity-60',
                hasAssignedShift && !isPast && 'border-primary/30 bg-primary/5',
                isPendingAssignment &&
                    'border-primary bg-primary/10 ring-primary/30 ring-2',
                selection.rangeAnchor === date &&
                    'border-primary ring-primary ring-2',
                isToday && 'ring-ring ring-2 ring-offset-1',
            )}
        >
            <button
                type="button"
                data-shift-calendar-date={date}
                disabled={isPast}
                aria-label={`${formatAppDate(date)}${isToday ? ', today' : ''}`}
                aria-pressed={isPendingAssignment}
                onClick={onSelect}
                onKeyDown={onKeyboardNavigate}
                className="focus-visible:ring-ring mb-1 flex size-7 items-center justify-center rounded-md text-sm font-semibold outline-none focus-visible:ring-2 disabled:cursor-not-allowed sm:size-8"
            >
                {dayNumber}
            </button>

            <div className="space-y-1">
                {isPendingAssignment ? (
                    <div className="border-primary text-primary rounded-md border border-dashed px-1.5 py-1 text-[10px] font-medium sm:text-xs">
                        New shift
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
                        onClick={onAddAnother}
                    >
                        <Plus className="size-3" />
                        Add another
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
