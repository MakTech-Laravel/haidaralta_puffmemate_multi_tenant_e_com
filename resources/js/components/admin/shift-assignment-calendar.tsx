import {
    eachDayOfInterval,
    endOfWeek,
    format,
    isSameMonth,
    parseISO,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import { useMemo, useState } from 'react';
import {
    datesInRange,
    defaultKindForDate,
    isoDate,
    monthsInRange,
    previewDay,
    upsertException,
} from '@/components/admin/shift-duty-calendar';
import type {
    ShiftDayException,
    ShiftDayKind,
    ShiftDutyDay,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatClock } from '@/lib/shift-format';
import { cn } from '@/lib/utils';

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

type ShiftAssignmentCalendarProps = {
    startDate: string;
    endDate: string;
    workingDays: number[];
    defaultTemplate?: ShiftTemplateOption;
    templates: ShiftTemplateOption[];
    exceptions: ShiftDayException[];
    otherDays?: ShiftDutyDay[];
    errors?: Record<string, string | undefined>;
    onChange: (exceptions: ShiftDayException[]) => void;
};

export function ShiftAssignmentCalendar({
    startDate,
    endDate,
    workingDays,
    defaultTemplate,
    templates,
    exceptions,
    otherDays = [],
    errors = {},
    onChange,
}: ShiftAssignmentCalendarProps) {
    const [selectedDate, setSelectedDate] = useState<string | null>(null);
    const [spanFrom, setSpanFrom] = useState('');
    const [spanTo, setSpanTo] = useState('');
    const [spanKind, setSpanKind] = useState<'leave' | 'override'>('leave');
    const [spanTemplateId, setSpanTemplateId] = useState('');

    const defaultTemplateId = defaultTemplate?.id ?? null;
    const exceptionMap = useMemo(() => {
        return new Map(exceptions.map((item) => [item.date, item]));
    }, [exceptions]);
    const months = monthsInRange(startDate, endDate);
    const ready = Boolean(startDate && endDate && defaultTemplate);

    const otherByDate = useMemo(() => {
        const grouped = new Map<string, ShiftDutyDay[]>();

        for (const day of otherDays) {
            const items = grouped.get(day.date) ?? [];
            const key = `${day.assignment_id}-${day.shift_template_id}`;

            if (items.some((item) => `${item.assignment_id}-${item.shift_template_id}` === key)) {
                continue;
            }

            items.push(day);
            grouped.set(day.date, items);
        }

        return grouped;
    }, [otherDays]);

    const selectedPreview = selectedDate
        ? previewDay(
              selectedDate,
              workingDays,
              defaultTemplate,
              exceptionMap.get(selectedDate),
              templates,
          )
        : null;

    const applyDay = (date: string, kind: ShiftDayKind, templateId: number | null) => {
        onChange(
            upsertException(
                exceptions,
                {
                    date,
                    kind,
                    shift_template_id: templateId,
                },
                workingDays,
                defaultTemplateId,
            ),
        );
    };

    const applySpan = () => {
        if (!spanFrom || !spanTo) {
            return;
        }

        const from = spanFrom <= spanTo ? spanFrom : spanTo;
        const to = spanFrom <= spanTo ? spanTo : spanFrom;
        let next = exceptions;

        for (const date of datesInRange(from, to)) {
            if (date < startDate || date > endDate) {
                continue;
            }

            next = upsertException(
                next,
                {
                    date,
                    kind: spanKind,
                    shift_template_id:
                        spanKind === 'override' ? Number(spanTemplateId) || null : null,
                },
                workingDays,
                defaultTemplateId,
            );
        }

        onChange(next);
    };

    return (
        <div className="space-y-5">
            {exceptions.map((day, index) => (
                <span key={day.date}>
                    <input
                        type="hidden"
                        name={`days[${index}][date]`}
                        value={day.date}
                    />
                    <input
                        type="hidden"
                        name={`days[${index}][kind]`}
                        value={day.kind}
                    />
                    {day.shift_template_id ? (
                        <input
                            type="hidden"
                            name={`days[${index}][shift_template_id]`}
                            value={day.shift_template_id}
                        />
                    ) : null}
                </span>
            ))}

            {!ready ? (
                <div className="text-muted-foreground rounded-2xl border px-5 py-10 text-sm">
                    Pick the person, the dates they work, and the usual shift.
                    Days fill in here.
                </div>
            ) : (
                <div className="grid gap-4 xl:grid-cols-2">
                    {months.map((month) => (
                        <MonthCard
                            key={isoDate(month)}
                            month={month}
                            startDate={startDate}
                            endDate={endDate}
                            workingDays={workingDays}
                            defaultTemplate={defaultTemplate}
                            templates={templates}
                            exceptionMap={exceptionMap}
                            otherByDate={otherByDate}
                            onSelect={setSelectedDate}
                        />
                    ))}
                </div>
            )}

            <InputError message={errors.days ?? errors['days.0.date']} />

            {ready ? (
                <div className="bg-card space-y-3 rounded-2xl border p-4">
                    <div>
                        <p className="text-sm font-medium">Apply to a date span</p>
                        <p className="text-muted-foreground text-sm">
                            Mark leave or a different shift across several days
                            at once.
                        </p>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <div className="flex flex-col gap-1.5">
                            <Label>From</Label>
                            <DatePicker
                                value={spanFrom}
                                min={startDate}
                                max={endDate}
                                placeholder="From"
                                onChange={setSpanFrom}
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label>To</Label>
                            <DatePicker
                                value={spanTo}
                                min={startDate}
                                max={endDate}
                                placeholder="To"
                                onChange={setSpanTo}
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label>Change</Label>
                            <Select
                                value={spanKind}
                                onValueChange={(value) =>
                                    setSpanKind(value as 'leave' | 'override')
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="leave">Leave</SelectItem>
                                    <SelectItem value="override">
                                        Different shift
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {spanKind === 'override' ? (
                            <div className="flex flex-col gap-1.5">
                                <Label>Shift</Label>
                                <Select
                                    value={spanTemplateId}
                                    onValueChange={setSpanTemplateId}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select shift" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {templates.map((template) => (
                                            <SelectItem
                                                key={template.id}
                                                value={String(template.id)}
                                            >
                                                {template.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        ) : null}
                        <div className="flex items-end">
                            <Button
                                type="button"
                                variant="secondary"
                                className="w-full"
                                disabled={
                                    !spanFrom ||
                                    !spanTo ||
                                    (spanKind === 'override' && !spanTemplateId)
                                }
                                onClick={applySpan}
                            >
                                Apply
                            </Button>
                        </div>
                    </div>
                </div>
            ) : null}

            <Dialog
                open={selectedDate !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelectedDate(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {selectedDate
                                ? format(
                                      parseISO(`${selectedDate}T00:00:00`),
                                      'EEE d MMM yyyy',
                                  )
                                : 'Day'}
                        </DialogTitle>
                        <DialogDescription>
                            Keep the usual shift, mark leave, or put them on a
                            different window for this day.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPreview?.template ? (
                        <p className="text-sm">
                            {selectedPreview.template.name} ·{' '}
                            {formatClock(selectedPreview.template.start_time)}–
                            {formatClock(selectedPreview.template.end_time)}
                        </p>
                    ) : (
                        <p className="text-muted-foreground text-sm">
                            {selectedPreview?.kind === 'leave'
                                ? 'Leave'
                                : 'Weekly off'}
                        </p>
                    )}
                    {selectedDate
                        ? otherByDate.get(selectedDate)?.map((day) => (
                              <p
                                  key={`${day.assignment_id}-${day.date}`}
                                  className="text-muted-foreground text-xs"
                              >
                                  Also on {day.shift_template_name}{' '}
                                  {formatClock(day.start_time)}–
                                  {formatClock(day.end_time)}
                              </p>
                          ))
                        : null}
                    <DialogFooter className="gap-2 sm:justify-start">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                if (!selectedDate) {
                                    return;
                                }

                                applyDay(
                                    selectedDate,
                                    defaultKindForDate(
                                        selectedDate,
                                        workingDays,
                                    ),
                                    defaultKindForDate(
                                        selectedDate,
                                        workingDays,
                                    ) === 'scheduled'
                                        ? defaultTemplateId
                                        : null,
                                );
                                setSelectedDate(null);
                            }}
                        >
                            Keep
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                if (!selectedDate) {
                                    return;
                                }

                                applyDay(selectedDate, 'leave', null);
                                setSelectedDate(null);
                            }}
                        >
                            Leave this day
                        </Button>
                    </DialogFooter>
                    <div className="flex flex-col gap-1.5">
                        <Label>Different shift</Label>
                        <Select
                            value=""
                            onValueChange={(templateId) => {
                                if (!selectedDate) {
                                    return;
                                }

                                applyDay(
                                    selectedDate,
                                    'override',
                                    Number(templateId),
                                );
                                setSelectedDate(null);
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Pick another shift" />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((template) => (
                                    <SelectItem
                                        key={template.id}
                                        value={String(template.id)}
                                    >
                                        {template.name} · {template.start_time}–
                                        {template.end_time}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function MonthCard({
    month,
    startDate,
    endDate,
    workingDays,
    defaultTemplate,
    templates,
    exceptionMap,
    otherByDate,
    onSelect,
}: {
    month: Date;
    startDate: string;
    endDate: string;
    workingDays: number[];
    defaultTemplate?: ShiftTemplateOption;
    templates: ShiftTemplateOption[];
    exceptionMap: Map<string, ShiftDayException>;
    otherByDate: Map<string, ShiftDutyDay[]>;
    onSelect: (date: string) => void;
}) {
    const gridStart = startOfWeek(startOfMonth(month), { weekStartsOn: 1 });
    const last = new Date(month.getFullYear(), month.getMonth() + 1, 0);
    const cells = eachDayOfInterval({
        start: gridStart,
        end: endOfWeek(last, { weekStartsOn: 1 }),
    });

    return (
        <section className="bg-card overflow-hidden rounded-2xl border">
            <header className="border-b px-4 py-3">
                <h3 className="text-sm font-semibold tracking-tight">
                    {format(month, 'MMMM yyyy')}
                </h3>
            </header>
            <div className="grid grid-cols-7 gap-px bg-border/60 p-px">
                {WEEKDAYS.map((label) => (
                    <div
                        key={label}
                        className="bg-muted/40 text-muted-foreground py-2 text-center text-[11px] font-medium"
                    >
                        {label}
                    </div>
                ))}
                {cells.map((day) => {
                    const date = isoDate(day);
                    const inMonth = isSameMonth(day, month);
                    const inRange = date >= startDate && date <= endDate;
                    const preview = previewDay(
                        date,
                        workingDays,
                        defaultTemplate,
                        exceptionMap.get(date),
                        templates,
                    );
                    const others = otherByDate.get(date) ?? [];

                    if (!inMonth) {
                        return <div key={date} className="bg-card min-h-20" />;
                    }

                    if (!inRange) {
                        return (
                            <div
                                key={date}
                                className="bg-card text-muted-foreground min-h-20 px-2 py-2 text-xs"
                            >
                                {format(day, 'd')}
                            </div>
                        );
                    }

                    return (
                        <button
                            key={date}
                            type="button"
                            onClick={() => onSelect(date)}
                            className={cn(
                                'bg-card hover:bg-muted/50 flex min-h-24 flex-col items-start gap-1 px-2 py-2 text-left transition-colors',
                                preview.kind === 'leave' && 'bg-primary/5',
                            )}
                        >
                            <span className="text-xs font-medium">
                                {format(day, 'd')}
                            </span>
                            <span
                                className={cn(
                                    'text-[11px] leading-tight font-medium',
                                    preview.kind === 'weekly_off' &&
                                        'text-muted-foreground',
                                    preview.kind === 'leave' && 'text-primary',
                                )}
                            >
                                {cellLabel(preview.kind, preview.template)}
                            </span>
                            {preview.template ? (
                                <span className="text-muted-foreground text-[10px] leading-tight tabular-nums">
                                    {formatClock(preview.template.start_time)}–
                                    {formatClock(preview.template.end_time)}
                                </span>
                            ) : null}
                            {others.map((dayRow) => (
                                <span
                                    key={`${dayRow.assignment_id}-${dayRow.shift_template_id}`}
                                    className="text-muted-foreground rounded-full border px-1.5 py-0.5 text-[10px] leading-tight"
                                >
                                    {dayRow.shift_template_name}{' '}
                                    {formatClock(dayRow.start_time)}–
                                    {formatClock(dayRow.end_time)}
                                </span>
                            ))}
                        </button>
                    );
                })}
            </div>
        </section>
    );
}

function cellLabel(
    kind: ShiftDayKind,
    template: ShiftTemplateOption | undefined,
): string {
    if (kind === 'leave') {
        return 'Leave';
    }

    if (kind === 'weekly_off') {
        return 'Off';
    }

    return template?.name ?? 'Shift';
}
