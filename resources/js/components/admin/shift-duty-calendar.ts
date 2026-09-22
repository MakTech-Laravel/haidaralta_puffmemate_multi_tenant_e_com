import {
    eachDayOfInterval,
    eachMonthOfInterval,
    format,
    getISODay,
    parseISO,
} from 'date-fns';
import type {
    ShiftDayException,
    ShiftDayKind,
    ShiftDutyDay,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';

export const MAX_DUTY_DAYS = 366;

export function isoDate(value: Date | string): string {
    if (typeof value === 'string') {
        return value.slice(0, 10);
    }

    return format(value, 'yyyy-MM-dd');
}

export function defaultKindForDate(
    date: string,
    workingDays: number[],
): Exclude<ShiftDayKind, 'override' | 'leave'> {
    const weekday = getISODay(parseISO(`${date}T00:00:00`));

    return workingDays.includes(weekday) ? 'scheduled' : 'weekly_off';
}

export function isDefaultException(
    exception: ShiftDayException,
    workingDays: number[],
    defaultTemplateId: number | null,
): boolean {
    const kind = defaultKindForDate(exception.date, workingDays);

    if (kind === 'weekly_off') {
        return exception.kind === 'weekly_off' && exception.shift_template_id === null;
    }

    return (
        exception.kind === 'scheduled' &&
        exception.shift_template_id === defaultTemplateId
    );
}

export function exceptionsFromSavedDays(
    days: ShiftDutyDay[],
    workingDays: number[],
    defaultTemplateId: number | null,
): ShiftDayException[] {
    return days
        .map((day) => ({
            date: day.date,
            kind: day.kind,
            shift_template_id: day.shift_template_id,
        }))
        .filter(
            (day) => !isDefaultException(day, workingDays, defaultTemplateId),
        );
}

export function previewDay(
    date: string,
    workingDays: number[],
    defaultTemplate: ShiftTemplateOption | undefined,
    exception: ShiftDayException | undefined,
    templates: ShiftTemplateOption[],
): {
    date: string;
    kind: ShiftDayKind;
    template: ShiftTemplateOption | undefined;
} {
    if (exception) {
        const template = exception.shift_template_id
            ? templates.find((item) => item.id === exception.shift_template_id)
            : undefined;

        return {
            date,
            kind: exception.kind,
            template:
                exception.kind === 'scheduled' || exception.kind === 'override'
                    ? template
                    : undefined,
        };
    }

    const kind = defaultKindForDate(date, workingDays);

    return {
        date,
        kind,
        template: kind === 'scheduled' ? defaultTemplate : undefined,
    };
}

export function datesInRange(startDate: string, endDate: string): string[] {
    if (!startDate || !endDate || endDate < startDate) {
        return [];
    }

    const start = parseISO(`${startDate}T00:00:00`);
    const end = parseISO(`${endDate}T00:00:00`);
    const span =
        Math.round((end.getTime() - start.getTime()) / 86_400_000) + 1;

    if (span > MAX_DUTY_DAYS) {
        return [];
    }

    return eachDayOfInterval({ start, end }).map((day) => isoDate(day));
}

export function monthsInRange(startDate: string, endDate: string): Date[] {
    if (!startDate || !endDate || endDate < startDate) {
        return [];
    }

    return eachMonthOfInterval({
        start: parseISO(`${startDate}T00:00:00`),
        end: parseISO(`${endDate}T00:00:00`),
    });
}

export function upsertException(
    exceptions: ShiftDayException[],
    next: ShiftDayException,
    workingDays: number[],
    defaultTemplateId: number | null,
): ShiftDayException[] {
    const without = exceptions.filter((item) => item.date !== next.date);

    if (isDefaultException(next, workingDays, defaultTemplateId)) {
        return without;
    }

    return [...without, next].sort((left, right) =>
        left.date.localeCompare(right.date),
    );
}
