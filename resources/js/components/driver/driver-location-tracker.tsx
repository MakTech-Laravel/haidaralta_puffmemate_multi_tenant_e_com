import { Link, usePage } from '@inertiajs/react';
import { MapPinOff } from 'lucide-react';
import { useDriverLocation } from '@/hooks/use-driver-location';
import type { DriverTrackingConfig } from '@/lib/driver-location';

const MESSAGES = {
    denied: 'Location is blocked. Allow it in your browser settings to keep working.',
    unavailable:
        'Weak GPS signal. Move to an open area and keep this screen on.',
    unsupported:
        'This device cannot share location. Use another phone to keep working.',
} as const;

/**
 * Mounted once in the driver layout. Shares the driver's position while on duty and shows a
 * warning bar when location is blocked or the signal is lost. Renders nothing otherwise.
 */
export function DriverLocationTracker() {
    const { driverTracking } = usePage<{
        driverTracking: DriverTrackingConfig | null;
    }>().props;
    const status = useDriverLocation(driverTracking);

    if (
        !driverTracking?.should_share ||
        status === 'idle' ||
        status === 'sharing'
    ) {
        return null;
    }

    return (
        <div
            role="alert"
            data-test="driver-location-warning"
            className="bg-destructive text-destructive-foreground sticky top-0 z-40 flex items-center gap-3 px-4 py-2.5 text-sm"
        >
            <MapPinOff className="size-4 shrink-0" aria-hidden />
            <p className="min-w-0 flex-1">{MESSAGES[status]}</p>
            <Link
                href={driverTracking.gate_url}
                className="shrink-0 font-semibold underline underline-offset-2"
            >
                Fix
            </Link>
        </div>
    );
}
