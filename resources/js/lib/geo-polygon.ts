import buffer from '@turf/buffer';
import { featureCollection, polygon } from '@turf/helpers';
import truncate from '@turf/truncate';
import union from '@turf/union';
import type {
    Feature,
    MultiPolygon,
    Polygon,
    Position,
} from 'geojson';

export type PolygonPoint = {
    lat: number;
    lng: number;
};

export type UnionRingsResult = {
    ring: PolygonPoint[];
    warning: string | null;
};

const DEFAULT_BUFFER_METERS = 50;
const MAX_VERTICES = 200;

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

function positionsToRing(positions: Position[]): PolygonPoint[] | null {
    const points: PolygonPoint[] = [];

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

    return closedRing(vertices);
}

function simplifyRing(ring: PolygonPoint[]): PolygonPoint[] {
    const vertices = uniqueVertices(ring);

    if (vertices.length <= MAX_VERTICES) {
        return closedRing(vertices);
    }

    const step = Math.ceil(vertices.length / MAX_VERTICES);
    const sampled: PolygonPoint[] = [];

    for (let i = 0; i < vertices.length; i += step) {
        sampled.push(vertices[i]);
    }

    if (sampled.length < 3) {
        return closedRing(vertices.slice(0, MAX_VERTICES));
    }

    return closedRing(sampled);
}

function outerRingsFromGeometry(
    geometry: Polygon | MultiPolygon,
): PolygonPoint[][] {
    if (geometry.type === 'Polygon') {
        const ring = positionsToRing(geometry.coordinates[0] ?? []);

        return ring ? [ring] : [];
    }

    const rings: PolygonPoint[][] = [];

    for (const part of geometry.coordinates) {
        const ring = positionsToRing(part[0] ?? []);

        if (ring) {
            rings.push(ring);
        }
    }

    return rings;
}

function largestRing(rings: PolygonPoint[][]): PolygonPoint[] | null {
    let best: PolygonPoint[] | null = null;
    let bestCount = 0;

    for (const ring of rings) {
        const count = uniqueVertices(ring).length;

        if (count > bestCount) {
            best = ring;
            bestCount = count;
        }
    }

    return best;
}

export function ringToPolygonFeature(
    ring: PolygonPoint[],
): Feature<Polygon> | null {
    const vertices = uniqueVertices(ring);

    if (vertices.length < 3) {
        return null;
    }

    const closed = closedRing(vertices);
    const coords: Position[] = closed.map((point) => [point.lng, point.lat]);

    try {
        return polygon([coords]);
    } catch {
        return null;
    }
}

export function featureToRing(
    feature: Feature<Polygon | MultiPolygon> | null | undefined,
): PolygonPoint[] | null {
    if (!feature?.geometry) {
        return null;
    }

    const rings = outerRingsFromGeometry(feature.geometry);

    if (rings.length === 0) {
        return null;
    }

    if (rings.length === 1) {
        return simplifyRing(rings[0]);
    }

    return simplifyRing(largestRing(rings) ?? rings[0]);
}

function bufferFeature(
    feature: Feature<Polygon | MultiPolygon>,
    meters: number,
): Feature<Polygon | MultiPolygon> | null {
    const buffered = buffer(feature, meters / 1000, {
        units: 'kilometers',
        steps: 8,
    });

    if (!buffered) {
        return null;
    }

    return truncate(buffered, {
        precision: 7,
        coordinates: 2,
    }) as Feature<Polygon | MultiPolygon>;
}

/**
 * Union two closed lat/lng rings into one. If parts stay disconnected after a
 * bridging buffer, keeps the largest outer ring and returns a warning.
 */
export function unionRings(
    a: PolygonPoint[],
    b: PolygonPoint[],
): UnionRingsResult | null {
    const featureA = ringToPolygonFeature(a);
    const featureB = ringToPolygonFeature(b);

    if (featureA === null || featureB === null) {
        return null;
    }

    let combined = union(featureCollection([featureA, featureB]));

    if (!combined) {
        return null;
    }

    if (combined.geometry.type === 'MultiPolygon') {
        const bridgedParts: Feature<Polygon | MultiPolygon>[] = [];

        for (const partCoords of combined.geometry.coordinates) {
            const partFeature = polygon(partCoords);
            const bridged = bufferFeature(partFeature, DEFAULT_BUFFER_METERS);

            if (bridged) {
                bridgedParts.push(bridged);
            }
        }

        if (bridgedParts.length >= 2) {
            const bridgedUnion = union(featureCollection(bridgedParts));

            if (bridgedUnion) {
                combined = bridgedUnion;
            }
        }
    }

    if (combined.geometry.type === 'Polygon') {
        const ring = featureToRing(combined);

        return ring ? { ring, warning: null } : null;
    }

    const rings = outerRingsFromGeometry(combined.geometry);
    const largest = largestRing(rings);

    if (!largest) {
        return null;
    }

    return {
        ring: simplifyRing(largest),
        warning:
            'Zones did not fully connect; largest merged outline kept — use Pad or draw to fill gaps.',
    };
}

/**
 * Expand a closed ring outward by the given metres (default 50).
 */
export function bufferRing(
    ring: PolygonPoint[],
    meters: number = DEFAULT_BUFFER_METERS,
): PolygonPoint[] | null {
    const feature = ringToPolygonFeature(ring);

    if (feature === null) {
        return null;
    }

    const buffered = bufferFeature(feature, meters);

    if (!buffered) {
        return null;
    }

    return featureToRing(buffered);
}

/**
 * Closed square ring around a pin (±delta degrees), matching PHP Polygon::squareAround.
 */
export function squareAround(
    lat: number,
    lng: number,
    delta: number = 0.01,
): PolygonPoint[] {
    return closedRing([
        { lat: Number((lat - delta).toFixed(7)), lng: Number((lng - delta).toFixed(7)) },
        { lat: Number((lat - delta).toFixed(7)), lng: Number((lng + delta).toFixed(7)) },
        { lat: Number((lat + delta).toFixed(7)), lng: Number((lng + delta).toFixed(7)) },
        { lat: Number((lat + delta).toFixed(7)), lng: Number((lng - delta).toFixed(7)) },
    ]);
}

/**
 * Prefer Nominatim boundary for query; otherwise square around the pin; then pad 50 m.
 */
export async function resolveAreaZoneRing(input: {
    query: string;
    lat: number;
    lng: number;
    countrycodes?: string;
    signal?: AbortSignal;
}): Promise<PolygonPoint[] | null> {
    const { searchPlacesWithGeometry, ringFromGeoJson } = await import(
        '@/lib/nominatim'
    );

    let ring: PolygonPoint[] | null = null;

    try {
        const places = await searchPlacesWithGeometry(input.query, input.signal, {
            countrycodes: input.countrycodes,
        });

        for (const place of places) {
            const fromGeo = ringFromGeoJson(place.geojson);

            if (fromGeo !== null) {
                ring = fromGeo;
                break;
            }
        }
    } catch {
        // Fall through to squareAround.
    }

    if (ring === null) {
        ring = squareAround(input.lat, input.lng);
    }

    const padded = bufferRing(ring, DEFAULT_BUFFER_METERS);

    return padded ?? ring;
}

export { DEFAULT_BUFFER_METERS };
