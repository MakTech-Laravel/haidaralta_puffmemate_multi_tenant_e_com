import { Check, MoreHorizontal, Pencil, RotateCcw, X } from 'lucide-react';
import type { ShiftAssignmentCalendarDay } from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatAppClock } from '@/lib/datetime';
import { cn } from '@/lib/utils';

export function ShiftDayActions({
    shift,
    replacing,
    removing,
    onReplace,
    onRemove,
}: {
    shift: ShiftAssignmentCalendarDay;
    replacing: boolean;
    removing: boolean;
    onReplace: () => void;
    onRemove: () => void;
}) {
    const label = shift.name ?? 'Shift';
    const time = [
        formatAppClock(shift.start_time),
        formatAppClock(shift.end_time),
    ]
        .filter(Boolean)
        .join('–');

    if (!shift.editable) {
        return (
            <div className="bg-primary/80 text-primary-foreground min-w-0 rounded-md px-1.5 py-1 text-[10px] leading-tight opacity-60 sm:text-xs">
                <span className="block truncate font-medium">{label}</span>
                {time ? (
                    <span className="block truncate opacity-80">{time}</span>
                ) : null}
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex min-w-0 items-center gap-1 rounded-md pl-1.5 text-[10px] leading-tight sm:text-xs',
                !replacing && !removing && 'bg-primary text-primary-foreground',
                replacing &&
                    'border-2 border-dashed border-sky-700 bg-sky-100 text-sky-950',
                removing &&
                    'border-destructive/70 bg-destructive/10 text-destructive border-2 border-dashed line-through',
            )}
        >
            <span className="min-w-0 flex-1 py-1">
                <span className="block truncate font-semibold">
                    {removing ? 'Removing' : replacing ? 'Changing' : label}
                </span>
                {removing || replacing ? (
                    <span className="block truncate font-medium opacity-80">
                        Unsaved
                    </span>
                ) : time ? (
                    <span className="block truncate opacity-80">{time}</span>
                ) : null}
            </span>
            {removing ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="hover:bg-destructive/15 size-7 shrink-0"
                    aria-label={`Undo removal of ${label}`}
                    onClick={(event) => {
                        event.stopPropagation();
                        onRemove();
                    }}
                >
                    <RotateCcw className="size-3.5" />
                </Button>
            ) : (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className={cn(
                                'size-7 shrink-0',
                                replacing
                                    ? 'hover:bg-sky-200/80'
                                    : 'hover:bg-background/20',
                            )}
                            aria-label={`Actions for ${label}`}
                            onClick={(event) => event.stopPropagation()}
                        >
                            {replacing ? (
                                <Check className="size-3.5" />
                            ) : (
                                <MoreHorizontal className="size-3.5" />
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <DropdownMenuItem onSelect={onReplace}>
                            <Pencil />
                            {replacing ? 'Undo change' : 'Change shift'}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            variant="destructive"
                            onSelect={onRemove}
                        >
                            <X />
                            Remove shift
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}
