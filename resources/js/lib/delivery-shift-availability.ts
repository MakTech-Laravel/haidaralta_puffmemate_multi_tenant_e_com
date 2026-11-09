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
