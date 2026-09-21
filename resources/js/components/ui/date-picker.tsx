import { format, isValid, parse } from 'date-fns';
import { CalendarIcon, XIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatAppDate } from '@/lib/datetime';
import { cn } from '@/lib/utils';

const DATE_VALUE_FORMAT = 'yyyy-MM-dd';

type DatePickerProps = {
    id?: string;
    value?: string | null;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    clearable?: boolean;
    className?: string;
    /** Inclusive lower bound (yyyy-MM-dd). */
    min?: string | null;
    /** Inclusive upper bound (yyyy-MM-dd). */
    max?: string | null;
    'aria-invalid'?: boolean;
};

function parseDateValue(value: string | null | undefined): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parse(value, DATE_VALUE_FORMAT, new Date());

    return isValid(parsed) ? parsed : undefined;
}

function toDateValue(date: Date): string {
    return format(date, DATE_VALUE_FORMAT);
}

export function DatePicker({
    id,
    value,
    onChange,
    placeholder = 'Select date',
    disabled = false,
    clearable = true,
    className,
    min,
    max,
    'aria-invalid': ariaInvalid,
}: DatePickerProps) {
    const [open, setOpen] = useState(false);
    const selected = parseDateValue(value);
    const minDate = parseDateValue(min);
    const maxDate = parseDateValue(max);

    const showClear = clearable && Boolean(selected);

    return (
        <div className={cn('relative w-full min-w-0', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-invalid={ariaInvalid}
                        aria-expanded={open}
                        className={cn(
                            'border-input h-9 w-full min-w-0 shrink justify-between gap-2 overflow-hidden bg-transparent px-3 font-normal shadow-xs',
                            'hover:bg-transparent',
                            showClear && 'pr-16',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <span className="min-w-0 flex-1 truncate text-left">
                            {selected
                                ? formatAppDate(toDateValue(selected)) ||
                                  format(selected, 'PPP')
                                : placeholder}
                        </span>
                        <CalendarIcon className="text-muted-foreground size-4 shrink-0" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-auto overflow-hidden p-0"
                    align="start"
                    sideOffset={4}
                >
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        captionLayout="dropdown"
                        startMonth={new Date(2000, 0)}
                        endMonth={new Date(2100, 11)}
                        disabled={(date) => {
                            const day = new Date(
                                date.getFullYear(),
                                date.getMonth(),
                                date.getDate(),
                            );

                            if (minDate) {
                                const minDay = new Date(
                                    minDate.getFullYear(),
                                    minDate.getMonth(),
                                    minDate.getDate(),
                                );

                                if (day < minDay) {
                                    return true;
                                }
                            }

                            if (maxDate) {
                                const maxDay = new Date(
                                    maxDate.getFullYear(),
                                    maxDate.getMonth(),
                                    maxDate.getDate(),
                                );

                                if (day > maxDay) {
                                    return true;
                                }
                            }

                            return false;
                        }}
                        onSelect={(date) => {
                            onChange(date ? toDateValue(date) : '');
                            setOpen(false);
                        }}
                    />
                </PopoverContent>
            </Popover>
            {showClear ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute top-1/2 right-8 z-10 size-7 -translate-y-1/2"
                    disabled={disabled}
                    onPointerDown={(event) => event.preventDefault()}
                    onClick={() => onChange('')}
                    aria-label="Clear date"
                >
                    <XIcon className="size-3.5" />
                </Button>
            ) : null}
        </div>
    );
}
