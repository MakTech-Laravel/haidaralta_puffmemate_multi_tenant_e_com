const MINUTES_IN_DAY = 24 * 60;

export function minutesFromMidnight(hhmm: string): number {
    const [hours, minutes] = hhmm.split(':').map(Number);

    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
        return 0;
    }

    return hours * 60 + minutes;
}

export function formatClock(hhmm: string | null | undefined): string {
    if (!hhmm) {
        return '—';
    }

    const [hoursRaw, minutesRaw] = hhmm.split(':');
    const hours = Number(hoursRaw);
    const minutes = Number(minutesRaw);

    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
        return hhmm;
    }

    const suffix = hours >= 12 ? 'PM' : 'AM';
    const hour = hours % 12 || 12;

    return `${hour}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

export function durationMinutes(
    start: string | null | undefined,
    end: string | null | undefined,
): number {
    if (!start || !end) {
        return 0;
    }

    const from = minutesFromMidnight(start);
    const to = minutesFromMidnight(end);

    if (to <= from) {
        return 0;
    }

    return to - from;
}

export function durationLabel(
    start: string | null | undefined,
    end: string | null | undefined,
): string {
    const minutes = durationMinutes(start, end);

    if (minutes <= 0) {
        return '';
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (rest === 0) {
        return `${hours}h`;
    }

    return `${hours}h ${rest}m`;
}

export function formatWindow(
    start: string | null | undefined,
    end: string | null | undefined,
): string {
    if (!start || !end) {
        return 'Set start and end';
    }

    return `${formatClock(start)} – ${formatClock(end)}`;
}

export function formatDayRange(
    startDate: string | null | undefined,
    endDate: string | null | undefined,
): string {
    if (!startDate || !endDate) {
        return 'Set dates';
    }

    const start = new Date(`${startDate}T00:00:00`);
    const end = new Date(`${endDate}T00:00:00`);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return `${startDate} – ${endDate}`;
    }

    const sameYear = start.getFullYear() === end.getFullYear();
    const startLabel = start.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: sameYear ? undefined : 'numeric',
    });
    const endLabel = end.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

    return `${startLabel} – ${endLabel}`;
}

export function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0] ?? '')
        .join('')
        .toUpperCase();
}

export type TimeBarSegment = {
    startPct: number;
    widthPct: number;
};

export function timeBarSegments(
    start: string | null | undefined,
    end: string | null | undefined,
): TimeBarSegment[] {
    if (!start || !end) {
        return [];
    }

    const from = minutesFromMidnight(start);
    const to = minutesFromMidnight(end);

    if (to <= from) {
        return [];
    }

    return [
        {
            startPct: (from / MINUTES_IN_DAY) * 100,
            widthPct: ((to - from) / MINUTES_IN_DAY) * 100,
        },
    ];
}

export function nowMarkerPct(now = new Date()): number {
    return ((now.getHours() * 60 + now.getMinutes()) / MINUTES_IN_DAY) * 100;
}

export function hourTickLabel(hour: number): string {
    if (hour === 0 || hour === 24) {
        return '12a';
    }

    if (hour === 12) {
        return '12p';
    }

    return hour < 12 ? `${hour}a` : `${hour - 12}p`;
}

export function windowCoversNow(
    start: string | null | undefined,
    end: string | null | undefined,
    now = new Date(),
): boolean {
    if (!start || !end) {
        return false;
    }

    const minutes = now.getHours() * 60 + now.getMinutes();
    const from = minutesFromMidnight(start);
    const to = minutesFromMidnight(end);

    if (to <= from) {
        return false;
    }

    return minutes >= from && minutes < to;
}

export function formatLiveClock(now: Date): string {
    return now.toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
}

export function formatLiveDate(now: Date): string {
    return now.toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}
