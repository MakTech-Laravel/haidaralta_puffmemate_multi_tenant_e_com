import { Link } from '@inertiajs/react';
import {
    Loader2,
    Maximize2,
    Navigation,
    Route as RouteIcon,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { DriverAssignedOrder } from '@/components/driver/driver-order-types';
import { MapView } from '@/components/map/map-view';
import { Button } from '@/components/ui/button';
import { distanceMeters } from '@/lib/driver-location';
import { useDriverPosition } from '@/lib/driver-position-store';
import type { LatLngTuple, MarkerSpec, PolylineSpec } from '@/lib/maps/types';
import { cn } from '@/lib/utils';
import { directions } from '@/routes/driver/route';
import { show as orderShow } from '@/routes/driver/orders';

type RoadRoute = {
    distance_meters: number;
    duration_seconds: number;
    points: LatLngTuple[];
};

type DriverRouteMapPanelProps = {
    stops: DriverAssignedOrder[];
    nextStop: DriverAssignedOrder | null;
    highlightId?: string | null;
    areaLabel: string | null;
    navigateHref: string;
};

const SYDNEY: LatLngTuple = [-33.8688, 151.2093];

function kmLabel(meters: number): string {
    if (meters < 1000) {
        return `${Math.round(meters)} m`;
    }

    const km = meters / 1000;

    return km >= 100 ? `${Math.round(km)} km` : `${km.toFixed(1)} km`;
}

function minutesLabel(seconds: number): string {
    return `${Math.max(1, Math.round(seconds / 60))} min`;
}

function stopKindLabel(
    focusStop: DriverAssignedOrder,
    nextStop: DriverAssignedOrder | null,
    highlightId: string | null,
): string {
    if (highlightId === focusStop.id) {
        return 'Selected';
    }

    if (focusStop.id === nextStop?.id) {
        return 'Next';
    }

    return 'Stop';
}

export function DriverRouteMapPanel({
    stops,
    nextStop,
    highlightId = null,
    areaLabel,
    navigateHref,
}: DriverRouteMapPanelProps) {
    const position = useDriverPosition();
    const [selectedId, setSelectedId] = useState<string | null>(null);
    const [route, setRoute] = useState<{
        stopId: string;
        road: RoadRoute;
    } | null>(null);
    const [loading, setLoading] = useState(false);
    const [routeError, setRouteError] = useState<string | null>(null);
    const [fullScreen, setFullScreen] = useState(false);

    const focusStop =
        stops.find((stop) => stop.id === (selectedId ?? highlightId)) ??
        nextStop ??
        stops[0] ??
        null;
    const pinned = stops.filter(
        (stop) => stop.latitude != null && stop.longitude != null,
    );
    const focusHasPin =
        focusStop?.latitude != null && focusStop?.longitude != null;

    const markers = useMemo<MarkerSpec[]>(() => {
        const stopMarkers = pinned.map((stop, index): MarkerSpec => ({
            id: `stop-${stop.id}`,
            lat: stop.latitude as number,
            lng: stop.longitude as number,
            kind: 'stop',
            label: String(stops.indexOf(stop) + 1 || index + 1),
            color: stop.id === focusStop?.id ? '#2B70DB' : '#6E6E73',
            title: stop.customerName,
            onClick: () => setSelectedId(stop.id),
        }));

        return position
            ? [
                  ...stopMarkers,
                  {
                      id: 'me',
                      lat: position.lat,
                      lng: position.lng,
                      kind: 'driver',
                      label: 'You',
                  },
              ]
            : stopMarkers;
    }, [pinned, stops, focusStop?.id, position]);

    const roadRoute =
        route && route.stopId === focusStop?.id ? route.road : null;
    const polylines = useMemo<PolylineSpec[]>(
        () => (roadRoute ? [{ id: 'road', points: roadRoute.points }] : []),
        [roadRoute],
    );

    const straightLine =
        position && focusHasPin
            ? distanceMeters(
                  { latitude: position.lat, longitude: position.lng },
                  {
                      latitude: focusStop?.latitude as number,
                      longitude: focusStop?.longitude as number,
                  },
              )
            : null;

    const distanceHint = roadRoute
        ? `${kmLabel(roadRoute.distance_meters)} · ${minutesLabel(roadRoute.duration_seconds)}`
        : straightLine !== null
          ? `${kmLabel(straightLine)} straight`
          : null;

    const loadRoute = async () => {
        if (!focusStop || loading) {
            return;
        }

        setLoading(true);
        setRouteError(null);

        try {
            const response = await fetch(directions.url(focusStop.id), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const body = (await response.json()) as {
                route?: RoadRoute | null;
                message?: string;
            };

            if (!response.ok || !body.route) {
                setRouteError(
                    body.message ?? 'No road route was found for this stop.',
                );

                return;
            }

            setRoute({ stopId: focusStop.id, road: body.route });
        } catch {
            setRouteError(
                'Could not load the road route. Check your connection.',
            );
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!fullScreen) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setFullScreen(false);
            }
        };

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [fullScreen]);

    const center: LatLngTuple = position
        ? [position.lat, position.lng]
        : pinned[0]
          ? [pinned[0].latitude as number, pinned[0].longitude as number]
          : SYDNEY;

    const mapFitKey = `${pinned.length}-${position ? 'me' : 'no'}-${roadRoute ? 'road' : 'none'}-${fullScreen ? 'fs' : 'card'}`;

    return (
        <section
            className={cn(
                'border-border overflow-hidden rounded-2xl border',
                fullScreen &&
                    'fixed inset-0 z-50 rounded-none border-0 bg-background',
            )}
            data-test="driver-route-map"
            data-fullscreen={fullScreen ? 'true' : 'false'}
        >
            <div className="relative isolate overflow-hidden">
                <MapView
                    center={center}
                    markers={markers}
                    polylines={polylines}
                    fitKey={mapFitKey}
                    className={cn(
                        'w-full',
                        fullScreen ? 'h-[100svh]' : 'h-72 sm:h-96',
                    )}
                />

                <div
                    className={cn(
                        'absolute top-2 right-2 z-10',
                        fullScreen &&
                            'top-[max(0.5rem,env(safe-area-inset-top))] right-[max(0.5rem,env(safe-area-inset-right))]',
                    )}
                >
                    {fullScreen ? (
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="secondary"
                            className="bg-background/95 shadow-sm"
                            onClick={() => setFullScreen(false)}
                            aria-label="Exit full screen"
                            data-test="driver-route-map-exit-fullscreen"
                        >
                            <X />
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="secondary"
                            className="bg-background/95 shadow-sm"
                            onClick={() => setFullScreen(true)}
                            aria-label="Open map full screen"
                            data-test="driver-route-map-fullscreen"
                        >
                            <Maximize2 />
                        </Button>
                    )}
                </div>

                {pinned.length === 0 ? (
                    <p className="bg-background/90 text-muted-foreground pointer-events-none absolute inset-x-2 top-12 z-10 rounded-md px-2.5 py-1.5 text-xs">
                        No map pins yet. Use Navigate for the address.
                    </p>
                ) : null}

                {fullScreen && focusStop ? (
                    <div
                        className={cn(
                            'border-border bg-background/95 absolute inset-x-2 z-10 max-w-sm rounded-xl border p-2.5 shadow-sm sm:right-2 sm:left-auto',
                            'bottom-[max(0.5rem,env(safe-area-inset-bottom))]',
                        )}
                    >
                        <p className="text-muted-foreground text-[0.65rem] font-medium tracking-wide uppercase">
                            {stopKindLabel(focusStop, nextStop, highlightId)}
                        </p>
                        <p className="text-foreground truncate text-sm font-semibold">
                            {focusStop.customerName}
                        </p>
                        <p className="text-muted-foreground truncate text-xs">
                            {focusStop.addressSummary}
                        </p>
                        {distanceHint ? (
                            <p className="text-muted-foreground mt-1 text-xs">
                                {distanceHint}
                            </p>
                        ) : null}
                        {routeError ? (
                            <p className="text-destructive mt-1 text-xs">
                                {routeError}
                            </p>
                        ) : null}
                        <div className="mt-2 flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                className="flex-1"
                                disabled={!position || !focusHasPin || loading}
                                onClick={() => void loadRoute()}
                            >
                                {loading ? (
                                    <Loader2
                                        className="animate-spin"
                                        data-icon="inline-start"
                                    />
                                ) : (
                                    <RouteIcon data-icon="inline-start" />
                                )}
                                Road route
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                asChild
                            >
                                <Link
                                    href={orderShow.url(focusStop.id)}
                                    prefetch
                                >
                                    Open
                                </Link>
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            {!fullScreen ? (
                <div className="space-y-2 px-3 py-3 sm:px-4">
                    {focusStop ? (
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-muted-foreground text-[0.65rem] font-medium tracking-wide uppercase">
                                    {stopKindLabel(
                                        focusStop,
                                        nextStop,
                                        highlightId,
                                    )}{' '}
                                    · {areaLabel ?? "Today's run"}
                                </p>
                                <p className="text-foreground truncate text-sm font-medium">
                                    {focusStop.customerName}
                                </p>
                                <p className="text-muted-foreground truncate text-xs">
                                    {distanceHint ?? focusStop.addressSummary}
                                </p>
                                {routeError ? (
                                    <p className="text-destructive mt-0.5 text-xs">
                                        {routeError}
                                    </p>
                                ) : null}
                            </div>
                            <div className="flex shrink-0 gap-1.5">
                                <Button
                                    type="button"
                                    size="icon-sm"
                                    variant="secondary"
                                    disabled={
                                        !position || !focusHasPin || loading
                                    }
                                    onClick={() => void loadRoute()}
                                    aria-label="Show road route"
                                >
                                    {loading ? (
                                        <Loader2 className="animate-spin" />
                                    ) : (
                                        <RouteIcon />
                                    )}
                                </Button>
                                <Button
                                    type="button"
                                    size="icon-sm"
                                    variant="outline"
                                    asChild
                                >
                                    <Link
                                        href={orderShow.url(focusStop.id)}
                                        prefetch
                                        aria-label="Open stop"
                                    >
                                        <Navigation />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="min-w-0">
                            <p className="text-foreground text-sm font-medium">
                                {areaLabel ?? "Today's run"}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {stops.length}{' '}
                                {stops.length === 1 ? 'drop' : 'drops'} on this
                                map
                            </p>
                        </div>
                    )}

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="w-full"
                        asChild
                    >
                        <a
                            href={navigateHref}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <Navigation data-icon="inline-start" />
                            Navigate
                        </a>
                    </Button>
                </div>
            ) : null}
        </section>
    );
}
