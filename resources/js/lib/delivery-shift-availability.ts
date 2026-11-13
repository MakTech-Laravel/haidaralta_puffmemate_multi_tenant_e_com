import type { StorefrontTimeSlot } from '@/types/storefront';

function pad2(value: number): string {
    return String(value).padStart(2, '0');
}

/** Local wall-clock as H:i:s for comparing with shift start/end. */
export function localClockTime(date: Date = new Date()): string {
    return `${pad2(date.getHours())}:${pad2(date.getMinutes())}:${pad2(date.getSeconds())}`;
}

function normalizeTime(value: string): string {
    const trimmed = value.trim();

    if (/^\d{2}:\d{2}$/.test(trimmed)) {
        return `${trimmed}:00`;
    }

    return trimmed;
}

/**
 * Same-day slots still bookable vs a wall-clock time: start not past, end not finished.
 */
export function filterUpcomingDeliverySlots(
    slots: StorefrontTimeSlot[],
    nowTime: string = localClockTime(),
): StorefrontTimeSlot[] {
    const current = normalizeTime(nowTime);

    return slots.filter((slot) => {
        if (!slot.start_time || !slot.end_time) {
            return true;
        }

        const start = normalizeTime(slot.start_time);
        const end = normalizeTime(slot.end_time);

        return start >= current && end > current;
    });
}

/** Delivery method id (server key) for Same day; mirrors DeliveryShiftAvailability::METHOD_SAME_DAY. */
export const SAME_DAY_METHOD_ID = 'regular';

/**
 * Shown under the delivery method when Same day is picked for an address outside the same-day
 * radius. Mirrors DeliveryShiftAvailability::sameDayOutOfRadiusMessage().
 */
export function sameDayOutOfRadiusMessage(radiusKm?: number | null): string {
    const km = radiusKm ?? 15;
    const label = Number.isInteger(km) ? String(km) : String(Number(km.toFixed(1)));

    return `Same day delivery is only available within ${label} km of our branches. Choose Late delivery or enter a closer address.`;
}

/** "08:00" → "8:00am", "21:30" → "9:30pm". */
export function formatShiftClock(time?: string): string | null {
    const match = /^(\d{1,2}):(\d{2})/.exec(time ?? '');

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const suffix = hours >= 12 ? 'pm' : 'am';

    return `${hours % 12 === 0 ? 12 : hours % 12}:${match[2]}${suffix}`;
}

/** "8:00am – 9:00am" from the shift times, falling back to the server label. */
export function shiftRangeLabel(slot: StorefrontTimeSlot): string {
    const start = formatShiftClock(slot.start_time);
    const end = formatShiftClock(slot.end_time);

    return start && end ? `${start} – ${end}` : slot.label;
}

/** Earliest start to latest end across the slots, e.g. "8:00am – 10:00pm". */
export function shiftWindowLabel(slots: StorefrontTimeSlot[]): string | null {
    if (slots.length === 0) {
        return null;
    }

    const first = slots[0];
    const last = slots[slots.length - 1];
    const start = formatShiftClock(first.start_time);
    const end = formatShiftClock(last.end_time);

    if (start && end) {
        return `${start} – ${end}`;
    }

    const labelStart = first.label.split('–')[0]?.trim();
    const labelParts = last.label.split('–');
    const labelEnd = labelParts[labelParts.length - 1]?.trim();

    return labelStart && labelEnd ? `${labelStart} – ${labelEnd}` : null;
}

export type DeliveryDateOption = {
    /** Y-m-d value sent to the server. */
    value: string;
    /** Short weekday, e.g. "Thu". */
    weekday: string;
    /** Day/month as shown in the picker, e.g. "12/11". */
    label: string;
};

/** Every date from `min` through `max` (Y-m-d, inclusive), capped for safety. */
export function deliveryDateOptions(
    min?: string | null,
    max?: string | null,
): DeliveryDateOption[] {
    const parse = (value?: string | null): number | null => {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value ?? '');

        return match
            ? Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
            : null;
    };
    const start = parse(min);
    const end = parse(max);

    if (start === null || end === null || end < start) {
        return [];
    }

    const weekdayFormat = new Intl.DateTimeFormat('en-AU', {
        weekday: 'short',
        timeZone: 'UTC',
    });
    const options: DeliveryDateOption[] = [];

    for (
        let time = start, count = 0;
        time <= end && count < 60;
        time += 86_400_000, count++
    ) {
        const date = new Date(time);
        const iso = date.toISOString().slice(0, 10);

        options.push({
            value: iso,
            weekday: weekdayFormat.format(date),
            label: `${pad2(date.getUTCDate())}/${pad2(date.getUTCMonth() + 1)}`,
        });
    }

    return options;
}

/** Y-m-d shifted by whole days (UTC maths, so no timezone drift). */
export function shiftIsoDate(iso: string, days: number): string | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);

    if (!match) {
        return null;
    }

    const time =
        Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])) +
        days * 86_400_000;

    return new Date(time).toISOString().slice(0, 10);
}