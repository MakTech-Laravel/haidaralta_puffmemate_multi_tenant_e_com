import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type PolygonPoint = {
    lat: number;
    lng: number;
};

type BranchAreaPolygonMapProps = {
    value: PolygonPoint[];
    onChange: (ring: PolygonPoint[]) => void;
    centerLat?: string;
    centerLng?: string;
    className?: string;
    /** When true, drawing tools are hidden (show-only). */
    readOnly?: boolean;
};

const DEFAULT_LAT = -33.8688;
const DEFAULT_LNG = 151.2093;

type LeafletMap = {
    remove: () => void;
    setView: (center: [number, number], zoom?: number) => void;
    fitBounds: (bounds: unknown, options?: object) => void;
    addControl: (control: unknown) => void;
    on: (event: string, handler: (e: unknown) => void) => void;
    off: (event: string, handler: (e: unknown) => void) => void;
};

type FeatureGroup = {
    clearLayers: () => void;
    addLayer: (layer: unknown) => void;
    getBounds: () => unknown;
    addTo: (map: unknown) => unknown;
};

function toNumber(value: string | undefined, fallback: number): number {
    if (value === undefined || value.trim() === '') {
        return fallback;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function uniqueVertices(ring: PolygonPoint[]): PolygonPoint[] {
    if (ring.length === 0) {
        return [];
    }

    const first = ring[0];
    const last = ring[ring.length - 1];

    if (
        ring.length > 1 &&
        first.lat === last.lat &&
        first.lng === last.lng
    ) {
        return ring.slice(0, -1);
    }

    return ring;
}

function closedRing(vertices: PolygonPoint[]): PolygonPoint[] {
    if (vertices.length === 0) {
        return [];
    }

    const first = vertices[0];
    const last = vertices[vertices.length - 1];

    if (first.lat === last.lat && first.lng === last.lng) {
        return vertices;
    }

    return [...vertices, { ...first }];
}

function ringFromLatLngs(latLngs: unknown): PolygonPoint[] {
    const outer = Array.isArray(latLngs) ? latLngs : [];
    const ring = Array.isArray(outer[0]) ? outer[0] : outer;

    if (!Array.isArray(ring)) {
        return [];
    }

    const points: PolygonPoint[] = [];

    for (const point of ring) {
        if (
            point &&
            typeof point === 'object' &&
            'lat' in point &&
            'lng' in point
        ) {
            const lat = Number((point as { lat: number }).lat);
            const lng = Number((point as { lng: number }).lng);

            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                points.push({
                    lat: Number(lat.toFixed(7)),
                    lng: Number(lng.toFixed(7)),
                });
            }
        }
    }

    return closedRing(uniqueVertices(points));
}

export function BranchAreaPolygonMap({
    value,
    onChange,
    centerLat,
    centerLng,
    className,
    readOnly = false,
}: BranchAreaPolygonMapProps) {
    const mapElement = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<LeafletMap | null>(null);
    const featureGroupRef = useRef<FeatureGroup | null>(null);
    const leafletRef = useRef<typeof import('leaflet') | null>(null);
    const onChangeRef = useRef(onChange);
    const [mapReady, setMapReady] = useState(false);

    onChangeRef.current = onChange;

    useEffect(() => {
        if (mapElement.current === null) {
            return;
        }

        let cancelled = false;
        let createdHandler: ((e: unknown) => void) | null = null;
        let editedHandler: ((e: unknown) => void) | null = null;
        let deletedHandler: ((e: unknown) => void) | null = null;
        let drawControl: { remove: () => void } | null = null;

        void (async () => {
            const leaflet = await import('leaflet');
            await import('leaflet-draw');

            if (cancelled || mapElement.current === null) {
                return;
            }

            leafletRef.current = leaflet;
            const L = leaflet.default;
            const startLat = toNumber(centerLat, DEFAULT_LAT);
            const startLng = toNumber(centerLng, DEFAULT_LNG);
            const map = L.map(mapElement.current).setView(
                [startLat, startLng],
                13,
            ) as unknown as LeafletMap;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map as never);

            const featureGroup = new L.FeatureGroup();
            featureGroup.addTo(map as never);
            featureGroupRef.current = featureGroup as unknown as FeatureGroup;
            mapRef.current = map;

            if (!readOnly) {
                drawControl = new (
                    L as unknown as {
                        Control: {
                            Draw: new (options: object) => {
                                remove: () => void;
                            };
                        };
                    }
                ).Control.Draw({
                    position: 'topright',
                    draw: {
                        polygon: {
                            allowIntersection: false,
                            showArea: false,
                            shapeOptions: {
                                color: '#0f766e',
                                weight: 2,
                                fillOpacity: 0.2,
                            },
                        },
                        polyline: false,
                        rectangle: false,
                        circle: false,
                        marker: false,
                        circlemarker: false,
                    },
                    edit: {
                        featureGroup,
                        remove: true,
                    },
                });

                map.addControl(drawControl);

                createdHandler = (event: unknown) => {
                    const e = event as {
                        layer: { getLatLngs: () => unknown };
                    };

                    featureGroup.clearLayers();
                    featureGroup.addLayer(e.layer as never);
                    onChangeRef.current(ringFromLatLngs(e.layer.getLatLngs()));
                };

                editedHandler = (event: unknown) => {
                    const e = event as {
                        layers: {
                            eachLayer: (
                                cb: (layer: {
                                    getLatLngs: () => unknown;
                                }) => void,
                            ) => void;
                        };
                    };
                    let next: PolygonPoint[] = [];

                    e.layers.eachLayer((layer) => {
                        next = ringFromLatLngs(layer.getLatLngs());
                    });

                    onChangeRef.current(next);
                };

                deletedHandler = () => {
                    onChangeRef.current([]);
                };

                const drawEvents = (
                    L as unknown as {
                        Draw: { Event: Record<string, string> };
                    }
                ).Draw.Event;

                map.on(drawEvents.CREATED, createdHandler);
                map.on(drawEvents.EDITED, editedHandler);
                map.on(drawEvents.DELETED, deletedHandler);
            }

            if (!cancelled) {
                setMapReady(true);
            }
        })();

        return () => {
            cancelled = true;
            setMapReady(false);
            const map = mapRef.current;
            const L = leafletRef.current?.default as
                | {
                      Draw?: { Event: Record<string, string> };
                  }
                | undefined;

            if (
                map &&
                L?.Draw &&
                createdHandler &&
                editedHandler &&
                deletedHandler
            ) {
                map.off(L.Draw.Event.CREATED, createdHandler);
                map.off(L.Draw.Event.EDITED, editedHandler);
                map.off(L.Draw.Event.DELETED, deletedHandler);
            }

            drawControl = null;
            featureGroupRef.current = null;
            mapRef.current?.remove();
            mapRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [readOnly]);

    useEffect(() => {
        const map = mapRef.current;
        const featureGroup = featureGroupRef.current;
        const leaflet = leafletRef.current;

        if (!mapReady || map === null || featureGroup === null || leaflet === null) {
            return;
        }

        const L = leaflet.default;
        const vertices = uniqueVertices(value);

        featureGroup.clearLayers();

        if (vertices.length < 3) {
            if (vertices.length === 0) {
                map.setView(
                    [
                        toNumber(centerLat, DEFAULT_LAT),
                        toNumber(centerLng, DEFAULT_LNG),
                    ],
                    13,
                );
            }

            return;
        }

        const latLngs = vertices.map(
            (point) => [point.lat, point.lng] as [number, number],
        );
        const layer = L.polygon(latLngs, {
            color: '#0f766e',
            weight: 2,
            fillOpacity: 0.2,
        });

        featureGroup.addLayer(layer);

        try {
            map.fitBounds(featureGroup.getBounds(), { padding: [24, 24] });
        } catch {
            // Degenerate bounds — ignore.
        }
    }, [value, centerLat, centerLng, mapReady]);

    const vertexCount = uniqueVertices(value).length;

    return (
        <div className={cn('branch-area-polygon-map flex flex-col gap-3', className)}>
            <div
                ref={mapElement}
                className="border-border h-64 w-full overflow-hidden rounded-xl border md:h-80"
            />
            {!readOnly ? (
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-muted-foreground text-xs">
                        {vertexCount < 3
                            ? 'Select an address above to load the zone outline.'
                            : 'Zone outline from address — edit on the map if needed.'}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={vertexCount === 0}
                        onClick={() => onChange([])}
                    >
                        Clear
                    </Button>
                </div>
            ) : null}
        </div>
    );
}
