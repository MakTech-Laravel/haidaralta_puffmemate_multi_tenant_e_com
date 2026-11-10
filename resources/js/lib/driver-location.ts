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
    /** Seconds since the device took this fix. Used instead of a clock time so a wrong phone clock cannot make the server reject it. */
    age_seconds: number;
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
        age_seconds: Math.max(
            0,
            Math.round((Date.now() - position.timestamp) / 1000),
        ),
    };
}

export async function sendReading(
    url: string,
    reading: LocationReading,
): Promise<SendResult> {
    return (await sendReadingDetailed(url, reading)).result;
}

export async function sendReadingDetailed(
    url: string,
    reading: LocationReading,
): Promise<{ result: SendResult; reason: string | null }> {
    const outcome = (result: SendResult, reason: string | null = null) => ({
        result,
        reason,
    });

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
            return outcome('stop');
        }

        if (response.status === 423) {
            return outcome('blocked');
        }

        // A redirect (e.g. session expired) lands on an HTML page, not our JSON.
        if (
            !response.ok ||
            response.redirected ||
            !response.headers.get('content-type')?.includes('json')
        ) {
            return outcome('error');
        }

        const body = (await response.json()) as {
            accepted?: boolean;
            reason?: string | null;
        };

        return body.accepted
            ? outcome('accepted')
            : outcome('ignored', body.reason ?? null);
    } catch {
        return outcome('error');
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

/**
 * One position for the permission prompt. Tries a precise fix first; when the device cannot give
 * one in time (typical for laptops and desktops without GPS) it falls back to the coarser
 * network position instead of failing. A denied permission is never retried.
 */
export function getCurrentPositionWithFallback(): Promise<GeolocationPosition> {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            resolve,
            (error) => {
                if (error.code === error.PERMISSION_DENIED) {
                    reject(error);

                    return;
                }

                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: false,
                    maximumAge: 60000,
                    timeout: 15000,
                });
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 },
        );
    });
}

export const GEOLOCATION_OPTIONS: PositionOptions = {
    enableHighAccuracy: true,
    maximumAge: 15000,
    timeout: 30000,
};
