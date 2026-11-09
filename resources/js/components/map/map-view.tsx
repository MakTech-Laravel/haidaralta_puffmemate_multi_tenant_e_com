import { useEffect, useRef } from 'react';
import { createMap } from '@/lib/maps/create-map';
import type {
    LatLngTuple,
    MapAdapter,
    MarkerSpec,
    PolylineSpec,
} from '@/lib/maps/types';
import { cn } from '@/lib/utils';

type MapViewProps = {
    center: LatLngTuple;
    zoom?: number;
    markers?: MarkerSpec[];
    polylines?: PolylineSpec[];
    /** Change this value to re-fit the map around all markers (and polylines). */
    fitKey?: string | number;
    /** Change this value to move the map to `center`. */
    centerKey?: string | number;
    onMapClick?: (lat: number, lng: number) => void;
    className?: string;
};

/**
 * A map that follows the platform's active provider. Markers are keyed by id, so updating a
 * marker's position glides it instead of redrawing, which keeps live maps cheap and smooth.
 */
export function MapView({
    center,
    zoom = 13,
    markers = [],
    polylines = [],
    fitKey,
    centerKey,
    onMapClick,
    className,
}: MapViewProps) {
    const element = useRef<HTMLDivElement | null>(null);
    const adapter = useRef<MapAdapter | null>(null);
    const clickHandler = useRef(onMapClick);
    const shownMarkers = useRef<Set<string>>(new Set());
    const shownLines = useRef<Set<string>>(new Set());
    const latest = useRef({ center, markers, polylines, fitKey, centerKey });

    clickHandler.current = onMapClick;
    latest.current = { center, markers, polylines, fitKey, centerKey };

    const sync = () => {
        const map = adapter.current;

        if (!map) {
            return;
        }

        const { markers: nextMarkers, polylines: nextLines } = latest.current;
        const markerIds = new Set(nextMarkers.map((marker) => marker.id));
        const lineIds = new Set(nextLines.map((line) => line.id));

        shownMarkers.current.forEach((id) => {
            if (!markerIds.has(id)) {
                map.removeMarker(id);
            }
        });
        shownLines.current.forEach((id) => {
            if (!lineIds.has(id)) {
                map.removePolyline(id);
            }
        });

        nextMarkers.forEach((marker) => map.upsertMarker(marker));
        nextLines.forEach((line) => map.setPolyline(line));

        shownMarkers.current = markerIds;
        shownLines.current = lineIds;
    };

    const fit = () => {
        const map = adapter.current;
        const { markers: nextMarkers, polylines: nextLines } = latest.current;

        if (!map) {
            return;
        }

        const points: LatLngTuple[] = [
            ...nextMarkers.map((marker): LatLngTuple => [
                marker.lat,
                marker.lng,
            ]),
            ...nextLines.flatMap((line) => line.points),
        ];

        map.fitBounds(points);
    };

    useEffect(() => {
        if (!element.current) {
            return;
        }

        let cancelled = false;
        const target = element.current;

        void createMap(target, latest.current.center, zoom).then((created) => {
            if (cancelled) {
                created.destroy();

                return;
            }

            adapter.current = created;
            created.onClick((lat, lng) => clickHandler.current?.(lat, lng));
            sync();
            fit();
        });

        return () => {
            cancelled = true;
            adapter.current?.destroy();
            adapter.current = null;
            shownMarkers.current = new Set();
            shownLines.current = new Set();
        };
        // The map is created once per mount; later changes flow through the effects below.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(sync, [markers, polylines]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        if (centerKey !== undefined) {
            adapter.current?.setView(center[0], center[1]);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [centerKey]);

    useEffect(() => {
        if (fitKey !== undefined) {
            fit();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fitKey]);

    return <div ref={element} className={cn('bg-muted', className)} />;
}
