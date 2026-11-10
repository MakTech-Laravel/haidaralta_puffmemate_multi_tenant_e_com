import {
    geocode as geocodeRoute,
    reverse as reverseRoute,
} from '@/routes/maps';

export type NominatimAddressParts = {
    city?: string;
    state?: string;
    postalCode?: string;
};

export type NominatimPlace = {
    displayName: string;
    latitude: string;
    longitude: string;
} & NominatimAddressParts;

export type NominatimGeoJsonGeometry = {
    type: string;
    coordinates: unknown;
};

export type NominatimPlaceWithGeometry = NominatimPlace & {
    geojson?: NominatimGeoJsonGeometry | null;
};

export type NominatimReverseResult = {
    address: string;
    countryCode?: string;
} & NominatimAddressParts;

export type LatLngPoint = {
    lat: number;
    lng: number;
};

export type NominatimSearchOptions = {
    countrycodes?: string;
};

type NominatimAddress = {
    city?: string;
    town?: string;
    village?: string;
    suburb?: string;
    municipality?: string;
    state?: string;
    postcode?: string;
    country_code?: string;
};

type NominatimSearchRow = {
    display_name?: string;
    lat?: string;
    lon?: string;
    address?: NominatimAddress;
    geojson?: NominatimGeoJsonGeometry | null;
};

const NOMINATIM_HEADERS = {
    Accept: 'application/json',
} as const;

function addressParts(address?: NominatimAddress): NominatimAddressParts {
    if (!address) {
        return {};
    }

    const city =
        address.city ||
        address.town ||
        address.village ||
        address.suburb ||
        address.municipality;

    return {
        ...(city ? { city } : {}),
        ...(address.state ? { state: address.state } : {}),
        ...(address.postcode ? { postalCode: address.postcode } : {}),
    };
}

function closeRing(points: LatLngPoint[]): LatLngPoint[] {
    if (points.length === 0) {
        return [];
    }

    const first = points[0];
    const last = points[points.length - 1];

    if (first.lat === last.lat && first.lng === last.lng) {
        return points;
    }

    return [...points, { ...first }];
}

function uniqueVertices(points: LatLngPoint[]): LatLngPoint[] {
    if (points.length === 0) {
        return [];
    }

    const first = points[0];
    const last = points[points.length - 1];

    if (points.length > 1 && first.lat === last.lat && first.lng === last.lng) {
        return points.slice(0, -1);
    }

    return points;
}

function positionsToRing(positions: unknown): LatLngPoint[] | null {
    if (!Array.isArray(positions) || positions.length === 0) {
        return null;
    }

    const points: LatLngPoint[] = [];

    for (const position of positions) {
        if (!Array.isArray(position) || position.length < 2) {
            continue;
        }

        const lng = Number(position[0]);
        const lat = Number(position[1]);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            continue;
        }

        points.push({
            lat: Number(lat.toFixed(7)),
            lng: Number(lng.toFixed(7)),
        });
    }

    const vertices = uniqueVertices(points);

    if (vertices.length < 3) {
        return null;
    }

    return closeRing(vertices);
}

/**
 * Convert Nominatim / GeoJSON geometry into a closed lat/lng ring.
 * Polygon → outer ring; MultiPolygon → largest outer ring by vertex count.
 */
export function ringFromGeoJson(
    geometry: NominatimGeoJsonGeometry | null | undefined,
): LatLngPoint[] | null {
    if (!geometry || typeof geometry !== 'object') {
        return null;
    }

    const { type, coordinates } = geometry;

    if (type === 'Polygon') {
        if (!Array.isArray(coordinates) || coordinates.length === 0) {
            return null;
        }

        return positionsToRing(coordinates[0]);
    }

    if (type === 'MultiPolygon') {
        if (!Array.isArray(coordinates) || coordinates.length === 0) {
            return null;
        }

        let best: LatLngPoint[] | null = null;
        let bestCount = 0;

        for (const polygon of coordinates) {
            if (!Array.isArray(polygon) || polygon.length === 0) {
                continue;
            }

            const ring = positionsToRing(polygon[0]);

            if (ring === null) {
                continue;
            }

            const count = uniqueVertices(ring).length;

            if (count > bestCount) {
                best = ring;
                bestCount = count;
            }
        }

        return best;
    }

    return null;
}

export async function reverseGeocode(
    lat: number,
    lng: number,
    signal?: AbortSignal,
): Promise<string | undefined> {
    const result = await reverseGeocodeDetails(lat, lng, signal);

    return result?.address;
}

type ProxyPlace = {
    displayName: string;
    latitude: string;
    longitude: string;
    city?: string;
    state?: string;
    postalCode?: string;
    countryCode?: string;
};

/** A two-letter country filter the map proxy understands (it takes one country only). */
function countryParam(options?: NominatimSearchOptions): string | undefined {
    const country = options?.countrycodes?.trim().toLowerCase();

    return country && country.length === 2 ? country : undefined;
}

/**
 * Address lookups go through the app's own /maps endpoints, so the active provider (and its
 * key) is chosen in the platform dashboard and answers are cached on the server.
 */
export async function reverseGeocodeDetails(
    lat: number,
    lng: number,
    signal?: AbortSignal,
    options?: NominatimSearchOptions,
): Promise<NominatimReverseResult | undefined> {
    const country = countryParam(options);
    const response = await fetch(
        reverseRoute.url({
            query: { lat, lng, ...(country ? { country } : {}) },
        }),
        { headers: NOMINATIM_HEADERS, credentials: 'same-origin', signal },
    );

    if (!response.ok) {
        return undefined;
    }

    const { result } = (await response.json()) as { result: ProxyPlace | null };

    if (!result) {
        return undefined;
    }

    return {
        address: result.displayName,
        ...(result.countryCode ? { countryCode: result.countryCode } : {}),
        ...(result.city ? { city: result.city } : {}),
        ...(result.state ? { state: result.state } : {}),
        ...(result.postalCode ? { postalCode: result.postalCode } : {}),
    };
}

export async function searchPlaces(
    query: string,
    signal?: AbortSignal,
    options?: NominatimSearchOptions,
): Promise<NominatimPlace[]> {
    const trimmed = query.trim();

    if (trimmed.length < 3) {
        return [];
    }

    const country = countryParam(options);
    const response = await fetch(
        geocodeRoute.url({
            query: { q: trimmed, ...(country ? { country } : {}) },
        }),
        { headers: NOMINATIM_HEADERS, credentials: 'same-origin', signal },
    );

    if (!response.ok) {
        throw new Error('Address search failed');
    }

    const { results } = (await response.json()) as { results: ProxyPlace[] };

    return results.map((place) => ({
        displayName: place.displayName,
        latitude: place.latitude,
        longitude: place.longitude,
        ...(place.city ? { city: place.city } : {}),
        ...(place.state ? { state: place.state } : {}),
        ...(place.postalCode ? { postalCode: place.postalCode } : {}),
    }));
}

/**
 * Zone outlines (polygon geometry) are only available from Nominatim itself, so the area
 * editor keeps calling it directly. This is an admin-only, low-volume flow.
 */
export async function searchPlacesWithGeometry(
    query: string,
    signal?: AbortSignal,
    options?: NominatimSearchOptions,
): Promise<NominatimPlaceWithGeometry[]> {
    const trimmed = query.trim();

    if (trimmed.length < 3) {
        return [];
    }

    const params = new URLSearchParams({
        q: trimmed,
        format: 'json',
        addressdetails: '1',
        limit: '5',
        polygon_geojson: '1',
        polygon_threshold: '0.0005',
    });

    if (options?.countrycodes) {
        params.set('countrycodes', options.countrycodes);
    }

    const response = await fetch(
        `https://nominatim.openstreetmap.org/search?${params.toString()}`,
        { headers: NOMINATIM_HEADERS, signal },
    );

    if (!response.ok) {
        throw new Error('Address search failed');
    }

    const payload = (await response.json()) as NominatimSearchRow[];

    return payload
        .filter(
            (
                row,
            ): row is NominatimSearchRow & {
                display_name: string;
                lat: string;
                lon: string;
            } => Boolean(row.display_name && row.lat && row.lon),
        )
        .map((row) => ({
            displayName: row.display_name,
            latitude: row.lat,
            longitude: row.lon,
            geojson: row.geojson ?? null,
            ...addressParts(row.address),
        }));
}
