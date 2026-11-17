/* eslint-disable @typescript-eslint/no-explicit-any */
import { glide } from './animate';
import { MARKER_COLORS } from './types';
import type {
    LatLngTuple,
    MapAdapter,
    MapDisplayConfig,
    MarkerSpec,
    PolylineSpec,
} from './types';

// The Google Maps script is loaded on demand so no dependency is added to the bundle.
type GoogleApi = any;

const GLIDE_MS = 800;

let scriptPromise: Promise<GoogleApi> | null = null;

function loadGoogleMaps(apiKey: string): Promise<GoogleApi> {
    const existing = (window as any).google?.maps;

    if (existing) {
        return Promise.resolve((window as any).google);
    }

    scriptPromise ??= new Promise<GoogleApi>((resolve, reject) => {
        const script = document.createElement('script');

        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly`;
        script.async = true;
        script.onload = () => resolve((window as any).google);
        script.onerror = () => {
            scriptPromise = null;
            script.remove();
            reject(new Error('Google Maps failed to load'));
        };

        document.head.appendChild(script);
    });

    return scriptPromise;
}

export async function createGoogleAdapter(
    element: HTMLElement,
    center: LatLngTuple,
    zoom: number,
    config: MapDisplayConfig,
): Promise<MapAdapter> {
    if (!config.api_key) {
        throw new Error('Google Maps browser key is missing');
    }

    const google = await loadGoogleMaps(config.api_key);
    const map = new google.maps.Map(element, {
        center: { lat: center[0], lng: center[1] },
        zoom,
        clickableIcons: false,
        streetViewControl: false,
        mapTypeControl: false,
        ...(config.map_id ? { mapId: config.map_id } : {}),
    });

    const markers = new Map<
        string,
        { marker: any; cancel: (() => void) | null }
    >();
    const lines = new Map<string, any>();

    const iconFor = (spec: MarkerSpec) => {
        const color = spec.muted
            ? MARKER_COLORS.muted
            : (spec.color ??
              MARKER_COLORS[spec.kind ?? 'pin'] ??
              MARKER_COLORS.primary);

        return {
            path: google.maps.SymbolPath.CIRCLE,
            scale: spec.kind === 'pin' || spec.kind === undefined ? 8 : 14,
            fillColor: color,
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
        };
    };

    const labelFor = (spec: MarkerSpec) =>
        spec.label
            ? {
                  text: spec.label.slice(0, 3),
                  color: '#ffffff',
                  fontSize: '11px',
                  fontWeight: '600',
              }
            : null;

    return {
        setView(lat, lng, nextZoom) {
            map.setCenter({ lat, lng });

            if (nextZoom !== undefined) {
                map.setZoom(nextZoom);
            }
        },
        getZoom: () => map.getZoom() ?? zoom,
        upsertMarker(spec) {
            const existing = markers.get(spec.id);

            if (!existing) {
                const marker = new google.maps.Marker({
                    map,
                    position: { lat: spec.lat, lng: spec.lng },
                    icon: iconFor(spec),
                    label: labelFor(spec),
                    title: spec.title,
                });

                if (spec.onClick) {
                    marker.addListener('click', spec.onClick);
                }

                markers.set(spec.id, { marker, cancel: null });

                return;
            }

            existing.cancel?.();
            existing.marker.setIcon(iconFor(spec));
            existing.marker.setLabel(labelFor(spec));

            const from = existing.marker.getPosition();

            existing.cancel = glide(
                [from.lat(), from.lng()],
                [spec.lat, spec.lng],
                GLIDE_MS,
                (lat, lng) => existing.marker.setPosition({ lat, lng }),
            );
        },
        removeMarker(id) {
            const existing = markers.get(id);

            existing?.cancel?.();
            existing?.marker.setMap(null);
            markers.delete(id);
        },
        setPolyline(spec: PolylineSpec) {
            lines.get(spec.id)?.setMap(null);
            lines.set(
                spec.id,
                new google.maps.Polyline({
                    map,
                    path: spec.points.map(([lat, lng]) => ({ lat, lng })),
                    strokeColor: spec.color ?? MARKER_COLORS.driver,
                    strokeOpacity: 0.85,
                    strokeWeight: 5,
                }),
            );
        },
        removePolyline(id) {
            lines.get(id)?.setMap(null);
            lines.delete(id);
        },
        fitBounds(points, padding = 40) {
            if (points.length === 0) {
                return;
            }

            const bounds = new google.maps.LatLngBounds();

            points.forEach(([lat, lng]) => bounds.extend({ lat, lng }));
            map.fitBounds(bounds, padding);

            google.maps.event.addListenerOnce(map, 'idle', () => {
                if ((map.getZoom() ?? 0) > 16) {
                    map.setZoom(16);
                }
            });
        },
        invalidateSize() {
            google.maps.event.trigger(map, 'resize');
        },
        onClick(handler) {
            map.addListener('click', (event: any) =>
                handler(event.latLng.lat(), event.latLng.lng()),
            );
        },
        destroy() {
            markers.forEach(({ cancel, marker }) => {
                cancel?.();
                marker.setMap(null);
            });
            markers.clear();
            lines.forEach((line) => line.setMap(null));
            lines.clear();
            google.maps.event.clearInstanceListeners(map);
            element.innerHTML = '';
        },
    };
}
