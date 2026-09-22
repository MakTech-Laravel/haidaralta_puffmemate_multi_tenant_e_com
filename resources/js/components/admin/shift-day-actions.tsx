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
                'bg-primary text-primary-foreground flex min-w-0 items-center gap-1 rounded-md pl-1.5 text-[10px] leading-tight sm:text-xs',
                replacing && 'ring-ring ring-2 ring-offset-1',
                removing && 'bg-muted text-muted-foreground line-through',
            )}
        >
            <span className="min-w-0 flex-1 py-1">
                <span className="block truncate font-medium">
                    {removing ? 'Removing' : replacing ? 'Changing' : label}
                </span>
                {time && !removing ? (
                    <span className="block truncate opacity-80">{time}</span>
                ) : null}
            </span>
            {removing ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="hover:bg-background/20 size-7 shrink-0"
                    aria-label={`Undo removal of ${label}`}
                    onClick={onRemove}
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
                            className="hover:bg-background/20 size-7 shrink-0"
                            aria-label={`Actions for ${label}`}
                        >
                            {replacing ? (
                                <Check className="size-3.5" />
                            ) : (
                                <MoreHorizontal className="size-3.5" />
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
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
