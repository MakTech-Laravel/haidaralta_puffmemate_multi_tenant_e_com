import { ChevronDownIcon, Clock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

/** Row height inside each wheel column. */
const ITEM_HEIGHT = 40;
/** How many rows are visible in the scroll viewport (taller = bigger pick area). */
const VISIBLE_ITEMS = 5;
const WHEEL_HEIGHT = ITEM_HEIGHT * VISIBLE_ITEMS;
const SETTLE_MS = 120;

const HOURS = Array.from({ length: 12 }, (_, index) => String(index + 1));
const MINUTES = Array.from({ length: 60 }, (_, index) =>
    String(index).padStart(2, '0'),
);
const PERIODS = ['AM', 'PM'] as const;

type Period = (typeof PERIODS)[number];

type ParsedTime = {
    hour12: string;
    minute: string;
    period: Period;
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

function parse24Hour(value: string): ParsedTime | null {
    const normalized = normalizeTime(value);

    if (!normalized) {
        return null;
    }

    const [hourPart, minutePart] = normalized.split(':');
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

    const period: Period = hour24 >= 12 ? 'PM' : 'AM';
    const hour12Value = hour24 % 12 === 0 ? 12 : hour24 % 12;

    return {
        hour12: String(hour12Value),
        minute: String(minute).padStart(2, '0'),
        period,
    };
}

function to24Hour(hour12: string, minute: string, period: Period): string {
    let hour = Number(hour12);

    if (period === 'AM') {
        hour = hour === 12 ? 0 : hour;
    } else {
        hour = hour === 12 ? 12 : hour + 12;
    }

    return `${String(hour).padStart(2, '0')}:${minute}`;
}

function formatDisplay(parsed: ParsedTime): string {
    return `${parsed.hour12}:${parsed.minute} ${parsed.period}`;
}

function defaultDraft(): ParsedTime {
    return {
        hour12: '9',
        minute: '00',
        period: 'AM',
    };
}

function clampIndex(index: number, length: number): number {
    return Math.min(length - 1, Math.max(0, index));
}

function indexFromScrollTop(scrollTop: number, length: number): number {
    return clampIndex(Math.round(scrollTop / ITEM_HEIGHT), length);
}

type TimeSelectProps = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    'aria-invalid'?: boolean;
};

type WheelColumnProps = {
    options: readonly string[];
    initialValue: string;
    onChange: (value: string) => void;
    className?: string;
    'aria-label': string;
};

function WheelColumn({
    options,
    initialValue,
    onChange,
    className,
    'aria-label': ariaLabel,
}: WheelColumnProps) {
    const listRef = useRef<HTMLDivElement>(null);
    const settleTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const onChangeRef = useRef(onChange);
    const [activeIndex, setActiveIndex] = useState(() =>
        Math.max(0, options.indexOf(initialValue)),
    );

    onChangeRef.current = onChange;

    // Only enough inset to center the active row — keeps the scroll viewport tall.
    const spacer = (WHEEL_HEIGHT - ITEM_HEIGHT) / 2;

    const notify = (index: number): void => {
        const next = options[index];

        if (!next) {
            return;
        }

        onChangeRef.current(next);
    };

    const alignToIndex = (index: number): void => {
        const node = listRef.current;

        if (!node) {
            return;
        }

        node.scrollTop = index * ITEM_HEIGHT;
    };

    const settleFromScroll = (): void => {
        const node = listRef.current;

        if (!node) {
            return;
        }

        const nextIndex = indexFromScrollTop(node.scrollTop, options.length);
        setActiveIndex(nextIndex);
        notify(nextIndex);
    };

    useEffect(() => {
        const startIndex = Math.max(0, options.indexOf(initialValue));
        setActiveIndex(startIndex);
        notify(startIndex);

        const timer = window.setTimeout(() => {
            alignToIndex(startIndex);
        }, 40);

        return () => {
            window.clearTimeout(timer);

            if (settleTimerRef.current) {
                clearTimeout(settleTimerRef.current);
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- mount / session only
    }, []);

    return (
        <div
            className={cn('relative min-w-0 flex-1', className)}
            aria-label={ariaLabel}
            role="listbox"
        >
            <div
                ref={listRef}
                onScroll={() => {
                    const node = listRef.current;

                    if (!node) {
                        return;
                    }

                    setActiveIndex(
                        indexFromScrollTop(node.scrollTop, options.length),
                    );

                    if (settleTimerRef.current) {
                        clearTimeout(settleTimerRef.current);
                    }

                    settleTimerRef.current = setTimeout(() => {
                        settleFromScroll();
                    }, SETTLE_MS);
                }}
                className="h-full snap-y snap-mandatory overflow-y-auto overscroll-contain touch-pan-y [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                <div style={{ height: spacer }} aria-hidden />
                {options.map((option, optionIndex) => {
                    const distance = Math.abs(optionIndex - activeIndex);
                    const isSelected = optionIndex === activeIndex;

                    return (
                        <div
                            key={option}
                            role="option"
                            aria-selected={isSelected}
                            onClick={() => {
                                if (settleTimerRef.current) {
                                    clearTimeout(settleTimerRef.current);
                                }

                                alignToIndex(optionIndex);
                                setActiveIndex(optionIndex);
                                notify(optionIndex);
                            }}
                            className={cn(
                                'flex w-full cursor-pointer snap-center items-center justify-center select-none',
                                isSelected
                                    ? 'text-foreground text-2xl font-semibold'
                                    : 'text-muted-foreground text-lg font-normal',
                            )}
                            style={{
                                height: ITEM_HEIGHT,
                                opacity: isSelected
                                    ? 1
                                    : Math.max(0.2, 1 - distance * 0.18),
                                transform: isSelected
                                    ? 'scale(1)'
                                    : `scale(${Math.max(0.8, 1 - distance * 0.05)})`,
                            }}
                        >
                            {option}
                        </div>
                    );
                })}
                <div style={{ height: spacer }} aria-hidden />
            </div>
        </div>
    );
}

export function TimeSelect({
    id,
    value,
    onChange,
    placeholder = 'Select time',
    disabled = false,
    className,
    'aria-invalid': ariaInvalid,
}: TimeSelectProps) {
    const [open, setOpen] = useState(false);
    const [session, setSession] = useState(0);
    const committed = parse24Hour(value);
    const draftRef = useRef<ParsedTime>(committed ?? defaultDraft());

    return (
        <Popover
            open={open}
            onOpenChange={(nextOpen) => {
                if (nextOpen) {
                    draftRef.current = committed ?? defaultDraft();
                    setSession((current) => current + 1);
                }

                setOpen(nextOpen);
            }}
        >
            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    disabled={disabled}
                    aria-invalid={ariaInvalid}
                    data-placeholder={!committed || undefined}
                    className={cn(
                        'border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*="text-"])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 aria-invalid:border-destructive flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
                        className,
                    )}
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <Clock className="size-4 shrink-0" />
                        <span className="truncate">
                            {committed
                                ? formatDisplay(committed)
                                : placeholder}
                        </span>
                    </span>
                    <ChevronDownIcon className="size-4 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent
                align="start"
                sideOffset={4}
                className="w-72 overflow-hidden p-0 shadow-lg"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <div className="relative" style={{ height: WHEEL_HEIGHT }}>
                    <div
                        aria-hidden
                        className="bg-muted/70 pointer-events-none absolute inset-x-2 top-1/2 z-10 h-11 -translate-y-1/2 rounded-lg"
                    />
                    {/* Thin fades so more of the scroll area stays usable */}
                    <div
                        aria-hidden
                        className="from-popover pointer-events-none absolute inset-x-0 top-0 z-20 h-8 bg-gradient-to-b to-transparent"
                    />
                    <div
                        aria-hidden
                        className="from-popover pointer-events-none absolute inset-x-0 bottom-0 z-20 h-8 bg-gradient-to-t to-transparent"
                    />

                    <div className="relative z-0 flex h-full px-1">
                        <WheelColumn
                            key={`hour-${session}`}
                            aria-label="Hour"
                            options={HOURS}
                            initialValue={draftRef.current.hour12}
                            onChange={(hour12) => {
                                draftRef.current = {
                                    ...draftRef.current,
                                    hour12,
                                };
                            }}
                        />
                        <WheelColumn
                            key={`minute-${session}`}
                            aria-label="Minute"
                            options={MINUTES}
                            initialValue={draftRef.current.minute}
                            onChange={(minute) => {
                                draftRef.current = {
                                    ...draftRef.current,
                                    minute,
                                };
                            }}
                        />
                        <WheelColumn
                            key={`period-${session}`}
                            aria-label="Period"
                            options={PERIODS}
                            initialValue={draftRef.current.period}
                            onChange={(period) => {
                                draftRef.current = {
                                    ...draftRef.current,
                                    period: period as Period,
                                };
                            }}
                            className="max-w-16"
                        />
                    </div>
                </div>

                <div className="flex items-center justify-end gap-1 border-t px-2 py-1.5">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-muted-foreground h-8 px-3"
                        onClick={() => setOpen(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-primary h-8 px-3 font-semibold"
                        onClick={() => {
                            const next = draftRef.current;
                            onChange(
                                to24Hour(
                                    next.hour12,
                                    next.minute,
                                    next.period,
                                ),
                            );
                            setOpen(false);
                        }}
                    >
                        OK
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
