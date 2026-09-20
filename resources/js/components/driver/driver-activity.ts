import type { LucideIcon } from 'lucide-react';
import { KeyRound, LogIn, LogOut, Pencil, ScrollText } from 'lucide-react';
import type { ActivityRow } from '@/components/activity-log/types';

export const driverActivityEventFilters = [
    'all',
    'login',
    'logout',
    'updated',
] as const;

export type DriverActivityEventFilter =
    (typeof driverActivityEventFilters)[number];

export function driverActivityEventLabel(event: string | null): string {
    switch (event) {
        case 'login':
            return 'Signed in';
        case 'logout':
            return 'Signed out';
        case 'updated':
            return 'Updated';
        case 'created':
            return 'Created';
        case 'deleted':
            return 'Removed';
        default:
            return event ?? 'Activity';
    }
}

export function driverActivityEventIcon(event: string | null): LucideIcon {
    switch (event) {
        case 'login':
            return LogIn;
        case 'logout':
            return LogOut;
        case 'updated':
            return Pencil;
        case 'created':
            return KeyRound;
        default:
            return ScrollText;
    }
}

export function driverActivityDayLabel(value: string | null): string {
    if (!value) {
        return 'Earlier';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return 'Earlier';
    }

    const today = new Date();
    const startOfToday = new Date(
        today.getFullYear(),
        today.getMonth(),
        today.getDate(),
    );
    const startOfDate = new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate(),
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

    return new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
    }).format(date);
}

export function driverActivityTimeLabel(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

export function driverActivityTitle(row: ActivityRow): string {
    const description = row.description.trim();

    if (description !== '' && description !== row.event) {
        return description;
    }

    return driverActivityEventLabel(row.event);
}

export function driverActivitySubjectLabel(
    subject: ActivityRow['subject'],
): string | null {
    if (subject === null) {
        return null;
    }

    if (subject.type.includes('FcmDeviceToken')) {
        return 'This device';
    }

    return subject.label;
}

export function groupDriverActivitiesByDay(
    rows: ActivityRow[],
): Array<{ label: string; items: ActivityRow[] }> {
    const groups: Array<{ label: string; items: ActivityRow[] }> = [];

    for (const row of rows) {
        const label = driverActivityDayLabel(row.created_at);
        const last = groups.at(-1);

        if (last && last.label === label) {
            last.items.push(row);
            continue;
        }

        groups.push({ label, items: [row] });
    }

    return groups;
}
