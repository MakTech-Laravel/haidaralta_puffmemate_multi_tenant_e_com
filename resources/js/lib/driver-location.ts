export type DriverTrackingConfig = {
    should_share: boolean;
    location_url: string;
    gate_url: string;
    heartbeat_seconds: number;
    min_send_interval_seconds: number;
    min_distance_meters: number;
    recheck_seconds: number;
};

export type LocationReading = {
    latitude: number;
    longitude: number;
    accuracy: number | null;
    speed: number | null;
    heading: number | null;
    recorded_at: string;
};

/**
 * accepted: stored as the driver's position
 * ignored: server skipped it (weak accuracy, stale, out of order); keep sharing
 * stop: the driver is no longer on duty, stop tracking
 * blocked: portal is gated until location is shared again
 * error: network or server problem; try again on the next reading
 */
export type SendResult = 'accepted' | 'ignored' | 'stop' | 'blocked' | 'error';

export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function toReading(position: GeolocationPosition): LocationReading {
    const { latitude, longitude, accuracy, speed, heading } = position.coords;

    return {
        latitude,
        longitude,
        accuracy: Number.isFinite(accuracy) ? accuracy : null,
        speed:
            speed !== null && Number.isFinite(speed)
                ? Math.max(speed, 0)
                : null,
        heading:
            heading !== null && Number.isFinite(heading)
                ? Math.round(heading) % 361
                : null,
        recorded_at: new Date(position.timestamp).toISOString(),
    };
}

export async function sendReading(
    url: string,
    reading: LocationReading,
): Promise<SendResult> {
    try {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify(reading),
        });

        if (response.status === 204) {
            return 'stop';
        }

        if (response.status === 423) {
            return 'blocked';
        }

        // A redirect (e.g. session expired) lands on an HTML page, not our JSON.
        if (
            !response.ok ||
            response.redirected ||
            !response.headers.get('content-type')?.includes('json')
        ) {
            return 'error';
        }

        const body = (await response.json()) as { accepted?: boolean };

        return body.accepted ? 'accepted' : 'ignored';
    } catch {
        return 'error';
    }
}

export function distanceMeters(
    a: Pick<LocationReading, 'latitude' | 'longitude'>,
    b: Pick<LocationReading, 'latitude' | 'longitude'>,
): number {
    const toRad = (degrees: number) => (degrees * Math.PI) / 180;
    const dLat = toRad(b.latitude - a.latitude);
    const dLng = toRad(b.longitude - a.longitude);
    const h =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(a.latitude)) *
            Math.cos(toRad(b.latitude)) *
            Math.sin(dLng / 2) ** 2;

    return 6371000 * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
}

export const GEOLOCATION_OPTIONS: PositionOptions = {
    enableHighAccuracy: true,
    maximumAge: 15000,
    timeout: 30000,
};
