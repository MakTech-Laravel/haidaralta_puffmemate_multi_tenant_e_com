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

    return (
        <div className={cn('flex w-full items-center gap-1.5', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-invalid={ariaInvalid}
                        className={cn(
                            'h-9 w-full min-w-0 justify-between gap-2 px-3 font-normal',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <span className="truncate">
                            {selected
                                ? format(selected, 'PPP')
                                : placeholder}
                        </span>
                        <CalendarIcon className="size-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-auto overflow-hidden p-0"
                    align="start"
                >
                    <Calendar
                        mode="single"
                        selected={selected}
                        defaultMonth={selected}
                        captionLayout="dropdown"
                        startMonth={new Date(2000, 0)}
                        endMonth={new Date(2100, 11)}
                        disabled={(date) => {
                            if (minDate && date < minDate) {
                                return true;
                            }

                            if (maxDate && date > maxDate) {
                                return true;
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
            {clearable && selected ? (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="size-9 shrink-0"
                    disabled={disabled}
                    onClick={() => onChange('')}
                    aria-label="Clear date"
                >
                    <XIcon className="size-4" />
                </Button>
            ) : null}
        </div>
    );
}
