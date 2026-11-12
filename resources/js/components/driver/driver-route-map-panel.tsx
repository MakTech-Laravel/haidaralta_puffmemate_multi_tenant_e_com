import { Link } from '@inertiajs/react';
import { Loader2, Navigation, Route as RouteIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { DriverAssignedOrder } from '@/components/driver/driver-order-types';
import { MapView } from '@/components/map/map-view';
import { Button } from '@/components/ui/button';
import { distanceMeters } from '@/lib/driver-location';
import { useDriverPosition } from '@/lib/driver-position-store';
import type { LatLngTuple, MarkerSpec, PolylineSpec } from '@/lib/maps/types';
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
    return meters < 1000
        ? `${Math.round(meters)} m`
        : `${(meters / 1000).toFixed(1)} km`;
}

function minutesLabel(seconds: number): string {
    return `${Math.max(1, Math.round(seconds / 60))} min`;
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

    const center: LatLngTuple = position
        ? [position.lat, position.lng]
        : pinned[0]
          ? [pinned[0].latitude as number, pinned[0].longitude as number]
          : SYDNEY;

    return (
        <section className="border-border overflow-hidden rounded-2xl border">
            <div className="relative" data-test="driver-route-map">
                <MapView
                    center={center}
                    markers={markers}
                    polylines={polylines}
                    fitKey={`${pinned.length}-${position ? 'me' : 'no'}-${roadRoute ? 'road' : 'none'}`}
                    className="h-72 w-full sm:h-96"
                />

                {pinned.length === 0 ? (
                    <p className="bg-background/90 text-muted-foreground pointer-events-none absolute inset-x-3 top-3 z-[1000] rounded-lg px-3 py-2 text-sm">
                        These stops have no map pin yet. Use Navigate to open
                        the address in your maps app.
                    </p>
                ) : null}

                {focusStop ? (
                    <div className="absolute inset-x-3 bottom-3 z-[1000] rounded-xl bg-white/95 p-3 shadow-lg sm:inset-x-auto sm:right-3 sm:left-auto sm:w-80">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            {highlightId === focusStop.id
                                ? 'Selected stop'
                                : focusStop.id === nextStop?.id
                                  ? 'Next stop'
                                  : 'Stop'}
                        </p>
                        <p className="text-foreground mt-0.5 truncate font-semibold">
                            {focusStop.customerName}
                        </p>
                        <p className="text-muted-foreground truncate text-sm">
                            {focusStop.addressSummary}
                        </p>

                        {roadRoute ? (
                            <p className="text-foreground mt-1.5 text-sm font-medium">
                                {kmLabel(roadRoute.distance_meters)} ·{' '}
                                {minutesLabel(roadRoute.duration_seconds)} by
                                road
                            </p>
                        ) : straightLine !== null ? (
                            <p className="text-muted-foreground mt-1.5 text-sm">
                                About {kmLabel(straightLine)} away in a straight
                                line
                            </p>
                        ) : null}

                        {routeError ? (
                            <p className="text-destructive mt-1.5 text-xs">
                                {routeError}
                            </p>
                        ) : null}

                        <div className="mt-2 flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
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
                                Show road route
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
                                    Open stop
                                </Link>
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div className="min-w-0">
                    <p className="text-foreground text-sm font-medium">
                        {areaLabel ?? "Today's run"}
                    </p>
                    <p className="text-muted-foreground text-sm">
                        {stops.length} {stops.length === 1 ? 'drop' : 'drops'}{' '}
                        on this map
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" asChild>
                    <a href={navigateHref} target="_blank" rel="noreferrer">
                        <Navigation data-icon="inline-start" />
                        Navigate
                    </a>
                </Button>
            </div>
        </section>
    );
}
