import { router, usePage } from '@inertiajs/react';
import { format, isValid, parseISO } from 'date-fns';

export type ApplicationDateTimeProps = {
    timezone?: string;
    dateFormat?: string;
    timeFormat?: string;
};

const DEFAULT_TIMEZONE = 'Asia/Dhaka';
const DEFAULT_DATE_FORMAT = 'Y-m-d';
const DEFAULT_TIME_FORMAT = 'H:i';

/**
 * Convert common PHP date() tokens to date-fns format tokens.
 */
export function phpDateFormatToDateFns(phpFormat: string): string {
    const tokens: Record<string, string> = {
        Y: 'yyyy',
        y: 'yy',
        m: 'MM',
        n: 'M',
        d: 'dd',
        j: 'd',
        M: 'MMM',
        F: 'MMMM',
        H: 'HH',
        G: 'H',
        h: 'hh',
        g: 'h',
        i: 'mm',
        s: 'ss',
        A: 'a',
        a: 'aaa',
    };

    return phpFormat.replace(
        /Y|y|m|n|d|j|M|F|H|G|h|g|i|s|A|a/g,
        (token) => tokens[token] ?? token,
    );
}

/**
 * Prefer an explicit settings object; otherwise read live Inertia shared props.
 */
export function resolveApplicationDateTime(
    application?: ApplicationDateTimeProps | null,
): ApplicationDateTimeProps | null {
    if (application) {
        return application;
    }

    try {
        const shared = router.page?.props?.application as
            | ApplicationDateTimeProps
            | undefined;

        return shared ?? null;
    } catch {
        return null;
    }
}

export function getApplicationDateTime(
    application?: ApplicationDateTimeProps | null,
): Required<ApplicationDateTimeProps> {
    const resolved = resolveApplicationDateTime(application);

    return {
        timezone: resolved?.timezone || DEFAULT_TIMEZONE,
        dateFormat: resolved?.dateFormat || DEFAULT_DATE_FORMAT,
        timeFormat: resolved?.timeFormat || DEFAULT_TIME_FORMAT,
    };
}

function parseValue(
    value: string | Date | number | null | undefined,
): Date | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (value instanceof Date) {
        return isValid(value) ? value : null;
    }

    if (typeof value === 'number') {
        const date = new Date(value);

        return isValid(date) ? date : null;
    }

    const parsed = parseISO(value);
    if (isValid(parsed)) {
        return parsed;
    }

    const fallback = new Date(value);

    return isValid(fallback) ? fallback : null;
}

/**
 * Build a Date whose local wall-clock matches the instant in `timeZone`.
 */
export function toWallClockDate(date: Date, timeZone: string): Date {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-US', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(date)
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    ) as Record<string, string>;

    return new Date(
        Number(parts.year),
        Number(parts.month) - 1,
        Number(parts.day),
        Number(parts.hour),
        Number(parts.minute),
        Number(parts.second),
    );
}

function formatWithSettings(
    value: string | Date | number | null | undefined,
    phpPattern: string,
    application?: ApplicationDateTimeProps | null,
): string {
    const date = parseValue(value);

    if (date === null) {
        return '';
    }

    const settings = getApplicationDateTime(application);
    const wallClock = toWallClockDate(date, settings.timezone);

    return format(wallClock, phpDateFormatToDateFns(phpPattern));
}

export function formatAppDate(
    value: string | Date | number | null | undefined,
    application?: ApplicationDateTimeProps | null,
): string {
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const settings = getApplicationDateTime(application);
        const [year, month, day] = value.split('-').map(Number);
        const wallClock = new Date(year, month - 1, day);

        if (!isValid(wallClock)) {
            return '';
        }

        return format(wallClock, phpDateFormatToDateFns(settings.dateFormat));
    }

    const settings = getApplicationDateTime(application);

    return formatWithSettings(value, settings.dateFormat, application);
}

export function formatAppTime(
    value: string | Date | number | null | undefined,
    application?: ApplicationDateTimeProps | null,
): string {
    const settings = getApplicationDateTime(application);

    return formatWithSettings(value, settings.timeFormat, application);
}

export function formatAppDateTime(
    value: string | Date | number | null | undefined,
    application?: ApplicationDateTimeProps | null,
): string {
    const settings = getApplicationDateTime(application);

    return formatWithSettings(
        value,
        `${settings.dateFormat} ${settings.timeFormat}`,
        application,
    );
}

/**
 * Format a schedule clock (`H:i` / `H:i:s`) with the app time format.
 * Does not apply timezone conversion — these are wall-clock duty times.
 */
export function formatAppClock(
    time: string | null | undefined,
    application?: ApplicationDateTimeProps | null,
): string {
    if (!time) {
        return '';
    }

    const match = String(time).trim().match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);

    if (!match) {
        return String(time);
    }

    const date = new Date(
        1970,
        0,
        1,
        Number(match[1]),
        Number(match[2]),
        Number(match[3] ?? 0),
    );
    const settings = getApplicationDateTime(application);

    return format(date, phpDateFormatToDateFns(settings.timeFormat));
}

/**
 * Hook for pages already inside Inertia context.
 */
export function useAppDateTime() {
    const { application } = usePage().props;

    return {
        formatDate: (value: string | Date | number | null | undefined) =>
            formatAppDate(value, application),
        formatTime: (value: string | Date | number | null | undefined) =>
            formatAppTime(value, application),
        formatDateTime: (value: string | Date | number | null | undefined) =>
            formatAppDateTime(value, application),
        formatClock: (value: string | null | undefined) =>
            formatAppClock(value, application),
        settings: getApplicationDateTime(application),
    };
}
