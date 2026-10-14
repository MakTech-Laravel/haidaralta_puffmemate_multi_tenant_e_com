import {
    addMonths,
    clamp,
    differenceInCalendarDays,
    endOfMonth,
    format,
    isSameDay,
    isLastDayOfMonth,
    isSameYear,
    isValid,
    parse,
    startOfDay,
    startOfMonth,
    subDays,
    subMonths,
    subYears,
} from 'date-fns';
import {
    ArrowRight,
    CalendarRange,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import {
    useCallback,
    useEffect,
    useRef,
    useState,
    type ComponentProps,
} from 'react';
import type { DateRange, DayButton } from 'react-day-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useDragScroll } from '@/hooks/use-drag-scroll';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

const DATE_VALUE_FORMAT = 'yyyy-MM-dd';

const RANGE_BAND = 'before:bg-primary/10 dark:before:bg-primary/20';

const MONTH_LABELS = Array.from({ length: 12 }, (_, index) =>
    format(new Date(2000, index, 1), 'MMM'),
);

type CalendarView = 'days' | 'months' | 'years';

const rangeCalendarClassNames = {
    month_caption: 'sr-only',
    weekdays: 'flex',
    weekday:
        'text-muted-foreground min-w-11 flex-1 pb-1 text-[11px] font-medium tracking-wide uppercase select-none',
    root: 'w-full',
    months: 'relative flex flex-col',
    month: 'flex w-full flex-col',
    month_grid: 'w-full border-collapse',
    week: 'mt-1 flex w-full',
    day: 'relative h-11 min-w-11 flex-1 p-0 text-center select-none before:absolute before:inset-y-0 first:before:rounded-l-full last:before:rounded-r-full',
    range_middle: cn('before:inset-x-0', RANGE_BAND),
    range_start: cn('before:right-0 before:left-1/2', RANGE_BAND),
    range_end: cn('before:right-1/2 before:left-0', RANGE_BAND),
    today: '',
    outside: '',
    disabled: '',
    hidden: 'invisible',
};

const rangeEdgeModifiers = {
    monthStart: (day: Date) => day.getDate() === 1,
    monthEnd: (day: Date) => isLastDayOfMonth(day),
};

const rangeEdgeClassNames = {
    monthStart: 'before:rounded-l-full',
    monthEnd: 'before:rounded-r-full',
    singleDay: 'before:hidden',
};

function RangeDayButton({
    className,
    day,
    modifiers,
    ...props
}: ComponentProps<typeof DayButton>) {
    const ref = useRef<HTMLButtonElement>(null);
    const isEdge = Boolean(modifiers.range_start || modifiers.range_end);

    useEffect(() => {
        if (modifiers.focused) {
            ref.current?.focus();
        }
    }, [modifiers.focused]);

    return (
        <button
            ref={ref}
            data-day={day.date.toLocaleDateString()}
            {...props}
            className={cn(
                'relative z-10 mx-auto flex size-11 items-center justify-center rounded-full text-sm tabular-nums transition-colors outline-none',
                'focus-visible:ring-ring/60 focus-visible:ring-2',
                !isEdge && 'hover:bg-primary/15',
                modifiers.range_middle && 'text-foreground font-medium',
                isEdge &&
                    'bg-primary text-primary-foreground font-semibold shadow-sm',
                modifiers.today &&
                    'after:absolute after:bottom-1.5 after:left-1/2 after:size-1 after:-translate-x-1/2 after:rounded-full',
                modifiers.today &&
                    (isEdge
                        ? 'after:bg-primary-foreground'
                        : 'text-primary font-semibold after:bg-primary'),
                modifiers.disabled &&
                    'text-muted-foreground/40 pointer-events-none',
                className,
            )}
        />
    );
}

export type DateRangeValue = {
    /** Inclusive start (yyyy-MM-dd). */
    from: string;
    /** Inclusive end (yyyy-MM-dd). */
    to: string;
};

type DateRangePreset = {
    label: string;
    range: (today: Date) => { from: Date; to: Date };
};

type RangeField = 'from' | 'to';

const DEFAULT_PRESETS: DateRangePreset[] = [
    { label: 'Today', range: (today) => ({ from: today, to: today }) },
    {
        label: 'Yesterday',
        range: (today) => ({ from: subDays(today, 1), to: subDays(today, 1) }),
    },
    {
        label: 'Last 7 days',
        range: (today) => ({ from: subDays(today, 6), to: today }),
    },
    {
        label: 'This month',
        range: (today) => ({ from: startOfMonth(today), to: today }),
    },
    {
        label: 'Last month',
        range: (today) => {
            const lastMonth = subMonths(today, 1);

            return { from: startOfMonth(lastMonth), to: endOfMonth(lastMonth) };
        },
    },
];

type DateRangePickerProps = {
    id?: string;
    value: DateRangeValue;
    onChange: (value: DateRangeValue) => void;
    /** Latest selectable day (yyyy-MM-dd); presets treat it as today. */
    max?: string | null;
    presets?: DateRangePreset[];
    /** Range restored by the Reset button; defaults to the first preset that ends today. */
    defaultValue?: DateRangeValue;
    title?: string;
    disabled?: boolean;
    className?: string;
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

export function formatDateRangeLabel(value: DateRangeValue): string {
    const from = parseDateValue(value.from);
    const to = parseDateValue(value.to);

    if (!from || !to) {
        return '';
    }

    if (isSameDay(from, to)) {
        return format(from, 'd MMM yyyy');
    }

    if (isSameYear(from, to)) {
        return `${format(from, 'd MMM')} – ${format(to, 'd MMM yyyy')}`;
    }

    return `${format(from, 'd MMM yyyy')} – ${format(to, 'd MMM yyyy')}`;
}

function matchesRange(
    range: { from: Date; to: Date },
    from: Date | undefined,
    to: Date | undefined,
): boolean {
    return (
        from !== undefined &&
        to !== undefined &&
        isSameDay(range.from, from) &&
        isSameDay(range.to, to)
    );
}

export function DateRangePicker({
    id,
    value,
    onChange,
    max,
    presets = DEFAULT_PRESETS,
    defaultValue,
    title = 'Select dates',
    disabled = false,
    className,
}: DateRangePickerProps) {
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<DateRange | undefined>();
    const [activeField, setActiveField] = useState<RangeField>('from');
    const [month, setMonth] = useState<Date>();
    const [view, setView] = useState<CalendarView>('days');
    const [presetRowRef, presetRowEdges] = useDragScroll<HTMLDivElement>();
    const revealActivePreset = useCallback(
        (element: HTMLButtonElement | null) => {
            element?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        },
        [],
    );
    const maxDate = parseDateValue(max);
    const today = maxDate ?? startOfDay(new Date());

    const valueFrom = parseDateValue(value.from);
    const valueTo = parseDateValue(value.to);
    const activePreset = presets.find((preset) =>
        matchesRange(preset.range(today), valueFrom, valueTo),
    );
    const draftFrom = draft?.from;
    const draftTo = draft?.to;
    const draftPreset = presets.find((preset) =>
        matchesRange(preset.range(today), draftFrom, draftTo ?? draftFrom),
    );
    const selectedDays = draftFrom
        ? differenceInCalendarDays(draftTo ?? draftFrom, draftFrom) + 1
        : 0;

    const isSingleDay =
        draftFrom !== undefined &&
        (draftTo === undefined || isSameDay(draftFrom, draftTo));

    const firstMonth = startOfMonth(subYears(today, 5));
    const lastMonth = startOfMonth(today);
    const shownMonth = month ?? lastMonth;
    const years = Array.from(
        { length: lastMonth.getFullYear() - firstMonth.getFullYear() + 1 },
        (_, index) => firstMonth.getFullYear() + index,
    );

    const showMonth = (date: Date): void => {
        setMonth(clamp(startOfMonth(date), { start: firstMonth, end: lastMonth }));
    };

    const toggleView = (target: Exclude<CalendarView, 'days'>): void => {
        setView((current) => (current === target ? 'days' : target));
    };

    const showRange = (range: DateRange | undefined): void => {
        setDraft(range);
        setActiveField('from');
        setView('days');
        showMonth(range?.to ?? range?.from ?? today);
    };

    const openChange = (nextOpen: boolean): void => {
        if (nextOpen) {
            showRange({ from: valueFrom, to: valueTo });
        }

        setOpen(nextOpen);
    };

    const apply = (range: { from: Date; to: Date }): void => {
        onChange({ from: toDateValue(range.from), to: toDateValue(range.to) });
        setOpen(false);
    };

    const choosePreset = (preset: DateRangePreset): void => {
        showRange(preset.range(today));
    };

    const reset = (): void => {
        const fallback = defaultValue
            ? {
                  from: parseDateValue(defaultValue.from),
                  to: parseDateValue(defaultValue.to),
              }
            : presets.find((preset) => isSameDay(preset.range(today).to, today))
                  ?.range(today);

        showRange(fallback);
    };

    const pickDay = (day: Date): void => {
        if (activeField === 'from' || !draftFrom || day < draftFrom) {
            setDraft({ from: day, to: undefined });
            setActiveField('to');

            return;
        }

        setDraft({ from: draftFrom, to: day });
        setActiveField('from');
    };

    const calendar = (
        <Calendar
            mode="range"
            selected={draft}
            onSelect={(_range, day) => pickDay(day)}
            month={shownMonth}
            onMonthChange={setMonth}
            startMonth={firstMonth}
            endMonth={lastMonth}
            hideNavigation
            disabled={{ after: today }}
            showOutsideDays={false}
            modifiers={{
                ...rangeEdgeModifiers,
                singleDay: isSingleDay ? draftFrom : false,
            }}
            modifiersClassNames={rangeEdgeClassNames}
            classNames={rangeCalendarClassNames}
            components={{ DayButton: RangeDayButton }}
            className="bg-transparent p-0"
        />
    );

    const headerToggle = (target: Exclude<CalendarView, 'days'>, label: string) => (
        <button
            type="button"
            onClick={() => toggleView(target)}
            aria-expanded={view === target}
            className={cn(
                'focus-visible:ring-ring/50 inline-flex h-9 items-center gap-1 rounded-lg px-2.5 text-base font-semibold transition-colors outline-none focus-visible:ring-[3px]',
                view === target
                    ? 'bg-primary/10 text-primary'
                    : 'text-foreground hover:bg-accent',
            )}
            data-test={`date-range-${target}-toggle`}
        >
            {label}
            <ChevronDown
                className={cn(
                    'size-4 opacity-60 transition-transform',
                    view === target && 'rotate-180',
                )}
            />
        </button>
    );

    const navButton = (direction: 'previous' | 'next') => {
        const target = addMonths(shownMonth, direction === 'previous' ? -1 : 1);
        const isOutOfRange = target < firstMonth || target > lastMonth;

        return (
            <Button
                type="button"
                variant="outline"
                size="icon"
                className={cn(
                    'size-9 rounded-full shadow-none',
                    view !== 'days' && 'invisible',
                )}
                disabled={isOutOfRange}
                onClick={() => showMonth(target)}
                aria-label={
                    direction === 'previous' ? 'Previous month' : 'Next month'
                }
            >
                {direction === 'previous' ? <ChevronLeft /> : <ChevronRight />}
            </Button>
        );
    };

    const gridOptionClass = (isActive: boolean) =>
        cn(
            'focus-visible:ring-ring/50 h-12 rounded-xl text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-35',
            isActive
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-muted/50 text-foreground hover:bg-primary/10',
        );

    const monthGrid = (
        <div className="grid grid-cols-3 gap-2" data-test="date-range-month-grid">
            {MONTH_LABELS.map((label, index) => {
                const option = new Date(shownMonth.getFullYear(), index, 1);

                return (
                    <button
                        key={label}
                        type="button"
                        disabled={option < firstMonth || option > lastMonth}
                        className={gridOptionClass(
                            index === shownMonth.getMonth(),
                        )}
                        onClick={() => {
                            showMonth(option);
                            setView('days');
                        }}
                    >
                        {label}
                    </button>
                );
            })}
        </div>
    );

    const yearGrid = (
        <div className="grid grid-cols-3 gap-2" data-test="date-range-year-grid">
            {years.map((year) => (
                <button
                    key={year}
                    type="button"
                    className={gridOptionClass(year === shownMonth.getFullYear())}
                    onClick={() => {
                        showMonth(new Date(year, shownMonth.getMonth(), 1));
                        setView('months');
                    }}
                >
                    {year}
                </button>
            ))}
        </div>
    );

    const calendarPanel = (
        <div className={cn('flex flex-col gap-3', !isMobile && 'w-84 p-3')}>
            <div className="flex items-center justify-between">
                {navButton('previous')}
                <div className="flex items-center gap-1">
                    {headerToggle('months', format(shownMonth, 'MMMM'))}
                    {headerToggle('years', format(shownMonth, 'yyyy'))}
                </div>
                {navButton('next')}
            </div>
            <div className="min-h-[18.5rem]">
                {view === 'days' && calendar}
                {view === 'months' && monthGrid}
                {view === 'years' && yearGrid}
            </div>
        </div>
    );

    const fieldBox = (field: RangeField, date: Date | undefined) => (
        <button
            type="button"
            onClick={() => setActiveField(field)}
            disabled={field === 'to' && !draftFrom}
            className={cn(
                'focus-visible:ring-ring/50 flex min-w-0 flex-col items-start gap-0.5 rounded-xl border px-3 py-2 text-left transition-colors outline-none focus-visible:ring-[3px]',
                activeField === field
                    ? 'border-primary ring-primary/20 bg-primary/5 ring-2'
                    : 'border-border bg-background',
            )}
            data-test={`date-range-${field}`}
        >
            <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {field === 'from' ? 'From' : 'To'}
            </span>
            <span
                className={cn(
                    'truncate text-sm font-semibold',
                    !date && 'text-muted-foreground font-normal',
                )}
            >
                {date ? format(date, 'd MMM yyyy') : 'Pick a day'}
            </span>
        </button>
    );

    const presetButtons = (orientation: 'row' | 'column') =>
        presets.map((preset) => {
            const isActive = draftPreset?.label === preset.label;

            return (
                <Button
                    key={preset.label}
                    ref={
                        orientation === 'row' && isActive
                            ? revealActivePreset
                            : undefined
                    }
                    type="button"
                    size="sm"
                    variant={
                        orientation === 'row'
                            ? isActive
                                ? 'default'
                                : 'outline'
                            : isActive
                              ? 'secondary'
                              : 'ghost'
                    }
                    className={cn(
                        orientation === 'row'
                            ? 'h-9 shrink-0 rounded-full px-4'
                            : 'h-9 w-full justify-start font-normal',
                        orientation === 'column' && isActive && 'font-medium',
                    )}
                    onClick={() => choosePreset(preset)}
                >
                    {preset.label}
                </Button>
            );
        });

    const footer = (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="ghost"
                className={cn(isMobile && 'h-12 px-5')}
                onClick={reset}
            >
                Reset
            </Button>
            <Button
                type="button"
                className={cn('flex-1', isMobile && 'h-12 text-base')}
                disabled={!draftFrom}
                onClick={() =>
                    draftFrom &&
                    apply({ from: draftFrom, to: draftTo ?? draftFrom })
                }
                data-test="date-range-apply"
            >
                {selectedDays > 0
                    ? `Show ${selectedDays} ${selectedDays === 1 ? 'day' : 'days'}`
                    : 'Apply'}
            </Button>
        </div>
    );

    const trigger = (
        <button
            id={id}
            type="button"
            disabled={disabled}
            className={cn(
                'border-input bg-background hover:bg-accent/40 focus-visible:border-ring focus-visible:ring-ring/50 flex w-full min-w-0 items-center gap-3 rounded-xl border px-3 py-2.5 text-left shadow-xs transition-colors outline-none focus-visible:ring-[3px] disabled:pointer-events-none disabled:opacity-50',
                className,
            )}
            data-test="date-range-trigger"
        >
            <span className="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                <CalendarRange className="size-4.5" />
            </span>
            <span className="flex min-w-0 flex-1 flex-col">
                <span className="text-muted-foreground text-xs">
                    {activePreset?.label ?? 'Custom range'}
                </span>
                <span className="text-foreground truncate text-sm font-semibold">
                    {formatDateRangeLabel(value)}
                </span>
            </span>
            <ChevronDown className="text-muted-foreground size-4 shrink-0" />
        </button>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={openChange}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent
                    side="bottom"
                    className="max-h-[92svh] gap-0 overflow-y-auto rounded-t-2xl p-0"
                    onOpenAutoFocus={(event) => event.preventDefault()}
                >
                    <div className="bg-muted mx-auto mt-2.5 h-1.5 w-10 shrink-0 rounded-full" />
                    <SheetHeader className="pb-3">
                        <SheetTitle>{title}</SheetTitle>
                        <SheetDescription className="sr-only">
                            Pick a start and end day, or tap a quick range.
                        </SheetDescription>
                    </SheetHeader>

                    <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-4">
                        {fieldBox('from', draftFrom)}
                        <ArrowRight className="text-muted-foreground size-4" />
                        {fieldBox('to', draftTo)}
                    </div>

                    <div
                        ref={presetRowRef}
                        className="flex scroll-px-4 gap-2 overflow-x-auto px-4 py-3 select-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        style={{
                            maskImage: `linear-gradient(to right, ${presetRowEdges.atStart ? '#000' : 'transparent'}, #000 2rem, #000 calc(100% - 2rem), ${presetRowEdges.atEnd ? '#000' : 'transparent'})`,
                        }}
                    >
                        {presetButtons('row')}
                    </div>

                    <div className="border-border border-t px-4 pt-3 pb-1">
                        {calendarPanel}
                    </div>

                    <div className="bg-background border-border sticky bottom-0 border-t px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                        {footer}
                    </div>
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Popover open={open} onOpenChange={openChange}>
            <PopoverTrigger asChild>{trigger}</PopoverTrigger>
            <PopoverContent
                className="w-auto overflow-hidden p-0"
                align="start"
                sideOffset={6}
            >
                <div className="flex">
                    <div className="border-border flex w-36 flex-col gap-0.5 border-r p-2">
                        {presetButtons('column')}
                    </div>
                    <div className="flex flex-col">
                        <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 pt-3">
                            {fieldBox('from', draftFrom)}
                            <ArrowRight className="text-muted-foreground size-4" />
                            {fieldBox('to', draftTo)}
                        </div>
                        {calendarPanel}
                        <div className="border-border border-t p-3">
                            {footer}
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
