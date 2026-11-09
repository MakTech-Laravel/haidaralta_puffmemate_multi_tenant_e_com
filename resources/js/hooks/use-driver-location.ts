import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import {
    GEOLOCATION_OPTIONS,
    distanceMeters,
    sendReading,
    toReading,
} from '@/lib/driver-location';
import type {
    DriverTrackingConfig,
    LocationReading,
} from '@/lib/driver-location';
import { publishDriverPosition } from '@/lib/driver-position-store';

export type DriverLocationStatus =
    'idle' | 'sharing' | 'denied' | 'unavailable' | 'unsupported';

const HEARTBEAT_POLL_MS = 15_000;

/**
 * Shares the signed-in driver's position while the server says they should be sharing.
 *
 * The browser fires watchPosition only when the position changes, so a slow timer asks for a
 * fresh fix when nothing was sent for a heartbeat. The server decides when sharing ends (204),
 * and the shared `driverTracking` prop is re-fetched periodically to notice a shift starting.
 */
export function useDriverLocation(
    config: DriverTrackingConfig | null,
): DriverLocationStatus {
    const [status, setStatus] = useState<DriverLocationStatus>('idle');
    const shouldShare = config?.should_share ?? false;
    const configRef = useRef(config);

    configRef.current = config;

    const recheckSeconds = config?.recheck_seconds;

    useEffect(() => {
        if (!recheckSeconds) {
            return;
        }

        const timer = window.setInterval(() => {
            router.reload({ only: ['driverTracking'] });
        }, recheckSeconds * 1000);

        return () => window.clearInterval(timer);
    }, [recheckSeconds]);

    useEffect(() => {
        if (!shouldShare) {
            setStatus('idle');

            return;
        }

        if (!('geolocation' in navigator)) {
            setStatus('unsupported');

            return;
        }

        let cancelled = false;
        let lastSentAt = 0;
        let lastSent: LocationReading | null = null;
        let inFlight = false;

        const submit = async (reading: LocationReading) => {
            const current = configRef.current;

            if (!current || inFlight) {
                return;
            }

            inFlight = true;
            lastSentAt = Date.now();
            lastSent = reading;

            const result = await sendReading(current.location_url, reading);

            inFlight = false;

            if (cancelled) {
                return;
            }

            if (result === 'stop') {
                router.reload({ only: ['driverTracking'] });
            } else if (result === 'blocked') {
                router.visit(current.gate_url);
            }
        };

        const onPosition = (position: GeolocationPosition) => {
            if (cancelled) {
                return;
            }

            setStatus('sharing');

            const current = configRef.current;

            if (!current) {
                return;
            }

            const reading = toReading(position);

            publishDriverPosition({
                lat: reading.latitude,
                lng: reading.longitude,
                ts: position.timestamp,
            });
            const elapsed = (Date.now() - lastSentAt) / 1000;
            const moved =
                lastSent === null ||
                distanceMeters(lastSent, reading) >=
                    current.min_distance_meters;

            if (
                elapsed >= current.min_send_interval_seconds &&
                (moved || elapsed >= current.heartbeat_seconds)
            ) {
                void submit(reading);
            }
        };

        const onError = (error: GeolocationPositionError) => {
            if (cancelled) {
                return;
            }

            setStatus(
                error.code === error.PERMISSION_DENIED
                    ? 'denied'
                    : 'unavailable',
            );
        };

        const watchId = navigator.geolocation.watchPosition(
            onPosition,
            onError,
            GEOLOCATION_OPTIONS,
        );

        const heartbeat = window.setInterval(() => {
            const current = configRef.current;

            if (
                current &&
                (Date.now() - lastSentAt) / 1000 >= current.heartbeat_seconds
            ) {
                navigator.geolocation.getCurrentPosition(onPosition, onError, {
                    ...GEOLOCATION_OPTIONS,
                    maximumAge: 30_000,
                });
            }
        }, HEARTBEAT_POLL_MS);

        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                navigator.geolocation.getCurrentPosition(
                    onPosition,
                    onError,
                    GEOLOCATION_OPTIONS,
                );
            }
        };

        document.addEventListener('visibilitychange', onVisible);

        let wakeLock: WakeLockSentinel | null = null;

        const acquireWakeLock = async () => {
            try {
                if (
                    'wakeLock' in navigator &&
                    document.visibilityState === 'visible'
                ) {
                    wakeLock = await navigator.wakeLock.request('screen');
                }
            } catch {
                // Wake lock is a best effort; sharing still works while the screen is on.
            }
        };

        void acquireWakeLock();
        document.addEventListener('visibilitychange', acquireWakeLock);

        return () => {
            cancelled = true;
            navigator.geolocation.clearWatch(watchId);
            window.clearInterval(heartbeat);
            document.removeEventListener('visibilitychange', onVisible);
            document.removeEventListener('visibilitychange', acquireWakeLock);
            void wakeLock?.release().catch(() => undefined);
        };
    }, [shouldShare]);

    return status;
}
