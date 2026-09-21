import {
    formatAppDate,
    formatAppTime,
    getApplicationDateTime,
    resolveApplicationDateTime,
    toWallClockDate,
    type ApplicationDateTimeProps,
} from '@/lib/datetime';

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const WEEK = 7 * DAY;

/**
 * Messenger-style timestamps: minutes, then hours, then weekday / date
 * using Application Settings timezone + formats.
 */
export function relativeTime(
    iso: string | null,
    application?: ApplicationDateTimeProps | null,
): string {
    if (!iso) {
        return '';
    }

    const then = new Date(iso);
    const elapsed = Date.now() - then.getTime();

    if (Number.isNaN(elapsed)) {
        return '';
    }

    if (elapsed < MINUTE) {
        return 'now';
    }

    if (elapsed < HOUR) {
        return `${Math.floor(elapsed / MINUTE)}m`;
    }

    if (elapsed < DAY) {
        return `${Math.floor(elapsed / HOUR)}h`;
    }

    const settings = resolveApplicationDateTime(application);

    if (elapsed < WEEK) {
        return (
            formatAppDate(iso, settings) ||
            then.toLocaleDateString(undefined, { weekday: 'short' })
        );
    }

    return formatAppDate(iso, settings) || '';
}

export function clockTime(
    iso: string | null,
    application?: ApplicationDateTimeProps | null,
): string {
    if (!iso) {
        return '';
    }

    return formatAppTime(iso, resolveApplicationDateTime(application)) || '';
}

/**
 * Day separator for chat threads (Today / Yesterday / formatted date).
 */
export function daySeparatorLabel(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const settings = getApplicationDateTime();
    const wall = toWallClockDate(date, settings.timezone);
    const todayWall = toWallClockDate(new Date(), settings.timezone);
    const startOfToday = new Date(
        todayWall.getFullYear(),
        todayWall.getMonth(),
        todayWall.getDate(),
    );
    const startOfDate = new Date(
        wall.getFullYear(),
        wall.getMonth(),
        wall.getDate(),
    );
    const diffDays = Math.round(
        (startOfToday.getTime() - startOfDate.getTime()) / 86_400_000,
    );

    if (diffDays === 0) {
        return 'Today';
    }

    if (diffDays === 1) {
        return 'Yesterday';
    }

    return formatAppDate(iso) || '';
}
