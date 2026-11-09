export type LatLngTuple = [number, number];

export type MarkerKind = 'pin' | 'driver' | 'stop' | 'branch';

export type MarkerSpec = {
    id: string;
    lat: number;
    lng: number;
    kind?: MarkerKind;
    /** Short text drawn inside the marker (initials, stop number). */
    label?: string;
    color?: string;
    /** Greyed out, e.g. a driver whose signal is stale. */
    muted?: boolean;
    title?: string;
    onClick?: () => void;
};

export type PolylineSpec = {
    id: string;
    points: LatLngTuple[];
    color?: string;
};

/**
 * What every map renderer (Leaflet, Google) must do. Screens talk to this interface only, so
 * switching the active provider in the dashboard never changes screen code.
 */
export interface MapAdapter {
    setView(lat: number, lng: number, zoom?: number): void;
    getZoom(): number;
    /** Adds the marker, or glides an existing marker with the same id to its new position. */
    upsertMarker(spec: MarkerSpec): void;
    removeMarker(id: string): void;
    setPolyline(spec: PolylineSpec): void;
    removePolyline(id: string): void;
    fitBounds(points: LatLngTuple[], padding?: number): void;
    onClick(handler: (lat: number, lng: number) => void): void;
    destroy(): void;
}

export type MapDisplayConfig = {
    key: string;
    label: string;
    type: 'leaflet' | 'google';
    tile_url?: string;
    attribution?: string;
    max_zoom?: number;
    api_key?: string | null;
    map_id?: string | null;
};

export type MapConfigPayload = {
    display: MapDisplayConfig;
    leaflet: MapDisplayConfig;
};

export const MARKER_COLORS: Record<string, string> = {
    primary: '#111827',
    driver: '#2563eb',
    stop: '#dc2626',
    branch: '#16a34a',
    muted: '#9ca3af',
};
