import { config as mapsConfigRoute } from '@/routes/maps';
import type { MapConfigPayload, MapDisplayConfig } from './types';

const OSM_FALLBACK: MapDisplayConfig = {
    key: 'osm',
    label: 'OpenStreetMap',
    type: 'leaflet',
    tile_url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; OpenStreetMap contributors',
    max_zoom: 19,
};

let pending: Promise<MapConfigPayload> | null = null;

/**
 * Loads the active map provider's browser settings once per page session. If the server cannot
 * answer, OpenStreetMap is used so a map never fails to open.
 */
export function fetchMapConfig(): Promise<MapConfigPayload> {
    pending ??= fetch(mapsConfigRoute.url(), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then(async (response) => {
            if (!response.ok) {
                throw new Error('Map config unavailable');
            }

            return (await response.json()) as MapConfigPayload;
        })
        .catch(() => {
            pending = null;

            return { display: OSM_FALLBACK, leaflet: OSM_FALLBACK };
        });

    return pending;
}

/** Tile settings that Leaflet can use, whatever the active display provider is. */
export async function fetchLeafletTiles(): Promise<MapDisplayConfig> {
    return (await fetchMapConfig()).leaflet;
}
