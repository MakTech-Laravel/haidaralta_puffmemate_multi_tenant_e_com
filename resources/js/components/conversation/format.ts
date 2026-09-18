const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const WEEK = 7 * DAY;

/**
 * Messenger-style timestamps: minutes, then hours, then a weekday, then a date.
 */
export function relativeTime(iso: string | null): string {
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

    if (elapsed < WEEK) {
        return then.toLocaleDateString(undefined, { weekday: 'short' });
    }

    return then.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
    });
}

export function clockTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
}
