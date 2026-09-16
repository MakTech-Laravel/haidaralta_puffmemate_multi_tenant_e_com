import { Clock, XIcon } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    WheelPicker,
    WheelPickerWrapper,
    type WheelPickerOption,
} from '@/components/ui/wheel-picker';
import { cn } from '@/lib/utils';

type Period = 'AM' | 'PM';

type TimeDraft = {
    hour: number;
    minute: number;
    period: Period;
};

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

const DEFAULT_DRAFT: TimeDraft = {
    hour: 8,
    minute: 0,
    period: 'AM',
};

const HOUR_OPTIONS: WheelPickerOption<number>[] = Array.from(
    { length: 12 },
    (_, index) => {
        const hour = index + 1;

        return {
            value: hour,
            label: String(hour),
        };
    },
);

const PERIOD_OPTIONS: WheelPickerOption<Period>[] = [
    { value: 'AM', label: 'AM' },
    { value: 'PM', label: 'PM' },
];

function clampStep(stepMinutes: number): number {
    return Math.max(1, Math.min(60, stepMinutes));
}

function snapMinute(minute: number, stepMinutes: number): number {
    const step = clampStep(stepMinutes);
    const snapped = Math.round(minute / step) * step;

    if (snapped >= 60) {
        return 60 - step;
    }

    return snapped;
}

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

function parseTime(
    value: string,
    stepMinutes: number,
): TimeDraft | null {
    if (!value) {
        return null;
    }

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
        return null;
    }

    return {
        hour: hour24 % 12 === 0 ? 12 : hour24 % 12,
        minute: snapMinute(minute, stepMinutes),
        period: hour24 >= 12 ? 'PM' : 'AM',
    };
}

function toHHmm(draft: TimeDraft): string {
    let hour24 = draft.hour % 12;

    if (draft.period === 'PM') {
        hour24 += 12;
    }

    return `${String(hour24).padStart(2, '0')}:${String(draft.minute).padStart(2, '0')}`;
}

function formatDisplay(value: string): string {
    const draft = parseTime(value, 1);

    if (!draft) {
        return value;
    }

    return `${draft.hour}:${String(draft.minute).padStart(2, '0')} ${draft.period}`;
}

function formatDraft(draft: TimeDraft): string {
    return `${draft.hour}:${String(draft.minute).padStart(2, '0')} ${draft.period}`;
}

function buildMinuteOptions(
    stepMinutes: number,
): WheelPickerOption<number>[] {
    const step = clampStep(stepMinutes);
    const options: WheelPickerOption<number>[] = [];

    for (let minute = 0; minute < 60; minute += step) {
        options.push({
            value: minute,
            label: String(minute).padStart(2, '0'),
        });
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
    const [draft, setDraft] = useState<TimeDraft>(DEFAULT_DRAFT);
    const draftRef = useRef(draft);
    const skipCommitRef = useRef(false);
    const seedHHmmRef = useRef(toHHmm(DEFAULT_DRAFT));
    const selected = normalizeTime(value);
    const minuteOptions = useMemo(
        () => buildMinuteOptions(stepMinutes),
        [stepMinutes],
    );

    draftRef.current = draft;

    function commitDraft(patch: Partial<TimeDraft>): void {
        const next = { ...draftRef.current, ...patch };

        draftRef.current = next;
        setDraft(next);

        const hhmm = toHHmm(next);

        if (skipCommitRef.current) {
            if (hhmm === seedHHmmRef.current) {
                return;
            }

            skipCommitRef.current = false;
        }

        if (hhmm !== selected) {
            onChange(hhmm);
        }
    }

    function handleOpenChange(nextOpen: boolean): void {
        if (nextOpen) {
            const parsed = parseTime(selected, stepMinutes);
            const nextDraft = parsed ?? DEFAULT_DRAFT;

            draftRef.current = nextDraft;
            seedHHmmRef.current = toHHmm(nextDraft);
            skipCommitRef.current = parsed === null;
            setDraft(nextDraft);
        }

        setOpen(nextOpen);
    }

    const showClear = clearable && Boolean(selected);

    return (
        <div className={cn('relative w-full min-w-0', className)}>
            <Popover open={open} onOpenChange={handleOpenChange}>
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
                            {selected ? formatDisplay(selected) : placeholder}
                        </span>
                        <Clock className="text-muted-foreground size-4 shrink-0" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-[18rem] overflow-hidden p-0"
                    align="start"
                    sideOffset={4}
                    onWheel={(event) => event.stopPropagation()}
                >
                    <div className="flex items-center justify-between border-b px-4 py-2.5">
                        <span className="text-sm font-medium">Time</span>
                        <span className="bg-muted rounded-full px-2.5 py-0.5 text-sm font-medium tabular-nums">
                            {formatDraft(draft)}
                        </span>
                    </div>
                    <WheelPickerWrapper>
                        <WheelPicker
                            infinite
                            options={HOUR_OPTIONS}
                            value={draft.hour}
                            onValueChange={(hour) => commitDraft({ hour })}
                        />
                        <WheelPicker
                            infinite
                            options={minuteOptions}
                            value={draft.minute}
                            onValueChange={(minute) =>
                                commitDraft({ minute })
                            }
                        />
                        <WheelPicker
                            options={PERIOD_OPTIONS}
                            value={draft.period}
                            onValueChange={(period) =>
                                commitDraft({ period })
                            }
                        />
                    </WheelPickerWrapper>
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
                    aria-label="Clear time"
                >
                    <XIcon className="size-3.5" />
                </Button>
            ) : null}
        </div>
    );
}
