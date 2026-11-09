import { Head, Link, router, usePage } from '@inertiajs/react';
import { Loader2, MapPin, MapPinOff } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    GEOLOCATION_OPTIONS,
    sendReading,
    toReading,
} from '@/lib/driver-location';
import type { DriverTrackingConfig } from '@/lib/driver-location';
import { logout } from '@/routes';

type Props = {
    intended: string;
};

type State = 'idle' | 'requesting' | 'denied' | 'weak' | 'error';

const COPY: Record<Exclude<State, 'idle' | 'requesting'>, string> = {
    denied: 'Location is blocked for this site. Open your browser settings, allow Location for this app, then try again.',
    weak: 'We could not get a good GPS fix. Move to an open area, keep this screen on, and try again.',
    error: 'Something went wrong while sending your location. Check your connection and try again.',
};

export default function DriverLocationRequired({ intended }: Props) {
    const { driverTracking } = usePage<{
        driverTracking: DriverTrackingConfig | null;
    }>().props;
    const [state, setState] = useState<State>('idle');

    const share = useCallback(() => {
        if (!driverTracking || !('geolocation' in navigator)) {
            setState('error');

            return;
        }

        setState('requesting');

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const result = await sendReading(
                    driverTracking.location_url,
                    toReading(position),
                );

                if (result === 'accepted' || result === 'stop') {
                    router.visit(intended);

                    return;
                }

                setState(result === 'ignored' ? 'weak' : 'error');
            },
            (error) =>
                setState(
                    error.code === error.PERMISSION_DENIED ? 'denied' : 'weak',
                ),
            { ...GEOLOCATION_OPTIONS, maximumAge: 0 },
        );
    }, [driverTracking, intended]);

    useEffect(() => {
        // Already allowed earlier: skip the button and share straight away.
        void navigator.permissions
            ?.query({ name: 'geolocation' })
            .then((permission) => {
                if (permission.state === 'granted') {
                    share();
                }
            })
            .catch(() => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const blocked = state === 'denied' || state === 'weak' || state === 'error';

    return (
        <>
            <Head title="Share your location" />

            <main className="bg-background text-foreground flex min-h-svh items-center justify-center px-4 py-10">
                <div className="border-border bg-card w-full max-w-md space-y-6 rounded-2xl border p-6 text-center shadow-sm sm:p-8">
                    <span className="bg-muted mx-auto flex size-14 items-center justify-center rounded-full">
                        {state === 'denied' ? (
                            <MapPinOff
                                className="text-destructive size-7"
                                aria-hidden
                            />
                        ) : (
                            <MapPin
                                className="text-primary size-7"
                                aria-hidden
                            />
                        )}
                    </span>

                    <div className="space-y-2">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Share your location to continue
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            You are on duty, so your live location is shared
                            with your branch while you work. It is not collected
                            outside your shift or while you are on a break.
                        </p>
                    </div>

                    {blocked && (
                        <p
                            role="alert"
                            data-test="driver-location-gate-message"
                            className="bg-muted text-foreground rounded-lg p-3 text-sm"
                        >
                            {COPY[state as keyof typeof COPY]}
                        </p>
                    )}

                    <Button
                        type="button"
                        size="lg"
                        className="h-12 w-full"
                        onClick={share}
                        disabled={state === 'requesting'}
                        data-test="driver-location-allow"
                    >
                        {state === 'requesting' ? (
                            <>
                                <Loader2
                                    className="animate-spin"
                                    data-icon="inline-start"
                                />
                                Getting your location…
                            </>
                        ) : blocked ? (
                            'Try again'
                        ) : (
                            'Allow location'
                        )}
                    </Button>

                    <Link
                        href={logout()}
                        as="button"
                        onClick={() => router.flushAll()}
                        className="text-muted-foreground hover:text-foreground text-sm underline underline-offset-2"
                    >
                        Log out
                    </Link>
                </div>
            </main>
        </>
    );
}

// Full-screen prompt: no driver chrome, so nothing else is reachable while it is shown.
DriverLocationRequired.layout = (page: ReactNode) => page;
