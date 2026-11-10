import { Head, Link, router, usePage } from '@inertiajs/react';
import { Loader2, MapPin, MapPinOff } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    getCurrentPositionWithFallback,
    sendReadingDetailed,
    toReading,
} from '@/lib/driver-location';
import type { DriverTrackingConfig } from '@/lib/driver-location';
import { detectDeviceOs, locationHelp } from '@/lib/location-help';
import type { LocationProblem } from '@/lib/location-help';
import { logout } from '@/routes';

type Props = {
    intended: string;
};

/** What a rejected reading means for the driver, in plain words. */
function problemFromReason(reason: string | null): LocationProblem {
    switch (reason) {
        case 'inaccurate':
            return 'inaccurate';
        case 'too_old':
            return 'old';
        case 'jump':
            return 'jump';
        default:
            return 'network';
    }
}

export default function DriverLocationRequired({ intended }: Props) {
    const { driverTracking } = usePage<{
        driverTracking: DriverTrackingConfig | null;
    }>().props;
    const [requesting, setRequesting] = useState(false);
    const [problem, setProblem] = useState<LocationProblem | null>(null);
    const [detail, setDetail] = useState<string | null>(null);
    const [accuracy, setAccuracy] = useState<number | null>(null);
    const os = useMemo(() => detectDeviceOs(), []);
    const help = problem ? locationHelp(problem, os) : null;

    const share = useCallback(async () => {
        if (!driverTracking || !('geolocation' in navigator)) {
            setProblem('unavailable');

            return;
        }

        setRequesting(true);
        setProblem(null);
        setDetail(null);
        setAccuracy(null);

        try {
            const position = await getCurrentPositionWithFallback();

            setAccuracy(Math.round(position.coords.accuracy));

            const { result, reason } = await sendReadingDetailed(
                driverTracking.location_url,
                toReading(position),
            );

            // out_of_order / superseded mean a fresh position is already stored, which is all
            // the gate needs. An approximate position (a computer without GPS) is accepted too.
            if (
                result === 'accepted' ||
                result === 'stop' ||
                reason === 'out_of_order' ||
                reason === 'superseded'
            ) {
                router.visit(intended);

                return;
            }

            setProblem(
                result === 'ignored' ? problemFromReason(reason) : 'network',
            );
        } catch (error) {
            const geolocationError = error as GeolocationPositionError;
            const code = geolocationError.code;

            setDetail(
                `${geolocationError.message ?? 'Unknown error'} (code ${code ?? '?'})`,
            );

            if (code === 1) {
                // The browser says "Allowed" but the device still refuses: the block is at the
                // device level, not in the browser's site settings.
                const state = await navigator.permissions
                    ?.query({ name: 'geolocation' })
                    .then((permission) => permission.state)
                    .catch(() => null);

                setProblem(state === 'granted' ? 'system' : 'denied');
            } else {
                setProblem(code === 3 ? 'timeout' : 'unavailable');
            }
        } finally {
            setRequesting(false);
        }
    }, [driverTracking, intended]);

    useEffect(() => {
        // Already allowed earlier: skip the button and share straight away.
        void navigator.permissions
            ?.query({ name: 'geolocation' })
            .then((permission) => {
                if (permission.state === 'granted') {
                    void share();
                }
            })
            .catch(() => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <>
            <Head title="Share your location" />

            <main className="bg-background text-foreground flex min-h-svh items-center justify-center px-4 py-8">
                <div className="border-border bg-card w-full max-w-md space-y-6 rounded-2xl border p-6 text-center shadow-sm sm:p-8">
                    <span className="bg-muted mx-auto flex size-14 items-center justify-center rounded-full">
                        {problem === 'denied' ? (
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

                    {help ? (
                        <div
                            role="alert"
                            data-test="driver-location-gate-message"
                            className="bg-muted text-foreground rounded-lg p-4 text-left text-sm"
                        >
                            <p className="font-semibold">{help.headline}</p>
                            {problem === 'inaccurate' && accuracy ? (
                                <p className="text-muted-foreground mt-1">
                                    Your device reported about{' '}
                                    {accuracy >= 1000
                                        ? `${(accuracy / 1000).toFixed(0)} km`
                                        : `${accuracy} m`}{' '}
                                    of error.
                                </p>
                            ) : null}
                            <ol className="mt-2 list-decimal space-y-1 pl-5">
                                {help.steps.map((step) => (
                                    <li key={step}>{step}</li>
                                ))}
                            </ol>
                            {detail ? (
                                <p className="text-muted-foreground mt-3 text-xs">
                                    Details: {detail}
                                </p>
                            ) : null}
                        </div>
                    ) : null}

                    <Button
                        type="button"
                        size="lg"
                        className="h-12 w-full"
                        onClick={() => void share()}
                        disabled={requesting}
                        data-test="driver-location-allow"
                    >
                        {requesting ? (
                            <>
                                <Loader2
                                    className="animate-spin"
                                    data-icon="inline-start"
                                />
                                Getting your location…
                            </>
                        ) : problem ? (
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
