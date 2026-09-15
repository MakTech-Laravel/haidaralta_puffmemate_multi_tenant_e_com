import { Clock, XIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type TimePickerProps = {
    id?: string;
    value?: string | null;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    clearable?: boolean;
    /** Minute step between options. Default: 15. */
    stepMinutes?: number;
    className?: string;
    'aria-invalid'?: boolean;
};

function normalizeTime(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const match = /^(\d{2}):(\d{2})/.exec(value);

    if (!match) {
        return '';
    }

    return `${match[1]}:${match[2]}`;
}

function formatDisplay(value: string): string {
    const [hourPart, minutePart] = value.split(':');
    const hour24 = Number(hourPart);
    const minute = Number(minutePart);

    if (
        Number.isNaN(hour24) ||
        Number.isNaN(minute) ||
        hour24 < 0 ||
        hour24 > 23 ||
        minute < 0 ||
        minute > 59
    ) {
        return value;
    }

    const period = hour24 >= 12 ? 'PM' : 'AM';
    const hour12 = hour24 % 12 === 0 ? 12 : hour24 % 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${period}`;
}

function buildOptions(stepMinutes: number): string[] {
    const step = Math.max(1, Math.min(60, stepMinutes));
    const options: string[] = [];

    for (let minutes = 0; minutes < 24 * 60; minutes += step) {
        const hour = Math.floor(minutes / 60);
        const minute = minutes % 60;
        options.push(
            `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`,
        );
    }

    return options;
}

export function TimePicker({
    id,
    value,
    onChange,
    placeholder = 'Select time',
    disabled = false,
    clearable = true,
    stepMinutes = 15,
    className,
    'aria-invalid': ariaInvalid,
}: TimePickerProps) {
    const [open, setOpen] = useState(false);
    const selected = normalizeTime(value);
    const options = useMemo(
        () => buildOptions(stepMinutes),
        [stepMinutes],
    );

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
                        aria-expanded={open}
                        className={cn(
                            'border-input h-9 w-full min-w-0 justify-between gap-2 bg-transparent px-3 font-normal shadow-xs',
                            'hover:bg-transparent',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <span className="truncate">
                            {selected ? formatDisplay(selected) : placeholder}
                        </span>
                        <Clock className="text-muted-foreground size-4 shrink-0" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-56 overflow-hidden p-0"
                    align="start"
                    sideOffset={4}
                >
                    <div className="max-h-64 overflow-y-auto p-1">
                        {options.map((option) => {
                            const isSelected = option === selected;

                            return (
                                <button
                                    key={option}
                                    type="button"
                                    className={cn(
                                        'hover:bg-accent hover:text-accent-foreground flex w-full items-center rounded-md px-2 py-1.5 text-left text-sm',
                                        isSelected &&
                                            'bg-accent text-accent-foreground font-medium',
                                    )}
                                    onClick={() => {
                                        onChange(option);
                                        setOpen(false);
                                    }}
                                >
                                    {formatDisplay(option)}
                                </button>
                            );
                        })}
                    </div>
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
                    aria-label="Clear time"
                >
                    <XIcon className="size-4" />
                </Button>
            ) : null}
        </div>
    );
}
