import type * as LeafletNamespace from 'leaflet';
import { glide } from './animate';
import { MARKER_COLORS } from './types';
import type {
    LatLngTuple,
    MapAdapter,
    MapDisplayConfig,
    MarkerSpec,
    PolylineSpec,
} from './types';

type Leaflet = typeof LeafletNamespace;

const GLIDE_MS = 800;

function markerHtml(spec: MarkerSpec): string {
    const color = spec.muted
        ? MARKER_COLORS.muted
        : (spec.color ??
          MARKER_COLORS[spec.kind ?? 'pin'] ??
          MARKER_COLORS.primary);
    const size = spec.kind === 'pin' || spec.kind === undefined ? 16 : 28;
    const label = (spec.label ?? '').slice(0, 3);

    return `<span style="display:flex;align-items:center;justify-content:center;width:${size}px;height:${size}px;border-radius:9999px;background:${color};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);color:#fff;font:600 11px/1 system-ui,sans-serif;">${label.replace(/[<>&"]/g, '')}</span>`;
}

export async function createLeafletAdapter(
    element: HTMLElement,
    center: LatLngTuple,
    zoom: number,
    tiles: MapDisplayConfig,
): Promise<MapAdapter> {
    const L: Leaflet = (await import('leaflet')).default;
    const map = L.map(element).setView(center, zoom);

    L.tileLayer(
        tiles.tile_url ?? 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: tiles.attribution ?? '',
            maxZoom: tiles.max_zoom ?? 19,
        },
    ).addTo(map);

    const markers = new Map<
        string,
        { marker: LeafletNamespace.Marker; cancel: (() => void) | null }
    >();
    const lines = new Map<string, LeafletNamespace.Polyline>();

    const iconFor = (spec: MarkerSpec): LeafletNamespace.DivIcon => {
        const size = spec.kind === 'pin' || spec.kind === undefined ? 16 : 28;

        return L.divIcon({
            className: '',
            html: markerHtml(spec),
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
        });
    };

    return {
        setView(lat, lng, nextZoom) {
            map.setView([lat, lng], nextZoom ?? map.getZoom());
        },
        getZoom: () => map.getZoom(),
        upsertMarker(spec) {
            const existing = markers.get(spec.id);

            if (!existing) {
                const marker = L.marker([spec.lat, spec.lng], {
                    icon: iconFor(spec),
                    title: spec.title,
                }).addTo(map);

                if (spec.onClick) {
                    marker.on('click', spec.onClick);
                }

                markers.set(spec.id, { marker, cancel: null });

                return;
            }

            existing.cancel?.();
            existing.marker.setIcon(iconFor(spec));

            const from = existing.marker.getLatLng();

            existing.cancel = glide(
                [from.lat, from.lng],
                [spec.lat, spec.lng],
                GLIDE_MS,
                (lat, lng) => existing.marker.setLatLng([lat, lng]),
            );
        },
        removeMarker(id) {
            const existing = markers.get(id);

            existing?.cancel?.();
            existing?.marker.remove();
            markers.delete(id);
        },
        setPolyline(spec: PolylineSpec) {
            lines.get(spec.id)?.remove();
            lines.set(
                spec.id,
                L.polyline(spec.points, {
                    color: spec.color ?? MARKER_COLORS.driver,
                    weight: 5,
                    opacity: 0.85,
                }).addTo(map),
            );
        },
        removePolyline(id) {
            lines.get(id)?.remove();
            lines.delete(id);
        },
        fitBounds(points, padding = 40) {
            if (points.length === 0) {
                return;
            }

            map.fitBounds(L.latLngBounds(points), {
                padding: [padding, padding],
                maxZoom: 16,
            });
        },
        invalidateSize() {
            map.invalidateSize();
        },
        onClick(handler) {
            map.on('click', (event: LeafletNamespace.LeafletMouseEvent) =>
                handler(event.latlng.lat, event.latlng.lng),
            );
        },
        destroy() {
            markers.forEach(({ cancel }) => cancel?.());
            markers.clear();
            lines.clear();
            map.remove();
        },
    };
}
