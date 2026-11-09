import { fetchMapConfig } from './config';
import { createGoogleAdapter } from './google-adapter';
import { createLeafletAdapter } from './leaflet-adapter';
import type { LatLngTuple, MapAdapter } from './types';

/**
 * Opens a map in the element using whichever provider the platform has made active. If the
 * active renderer cannot start (missing key, script blocked), the map still opens on the
 * OpenStreetMap/Leaflet tiles instead of showing an empty box.
 */
export async function createMap(
    element: HTMLElement,
    center: LatLngTuple,
    zoom: number,
): Promise<MapAdapter> {
    const { display, leaflet } = await fetchMapConfig();

    if (display.type === 'google') {
        try {
            return await createGoogleAdapter(element, center, zoom, display);
        } catch {
            element.innerHTML = '';
        }
    }

    return createLeafletAdapter(
        element,
        center,
        zoom,
        display.type === 'leaflet' ? display : leaflet,
    );
}
