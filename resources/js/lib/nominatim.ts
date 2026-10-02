export type NominatimPlace = {
    displayName: string;
    latitude: string;
    longitude: string;
};

type NominatimSearchRow = {
    display_name?: string;
    lat?: string;
    lon?: string;
};

const NOMINATIM_HEADERS = {
    Accept: 'application/json',
} as const;

export async function reverseGeocode(
    lat: number,
    lng: number,
    signal?: AbortSignal,
): Promise<string | undefined> {
    const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
        { headers: NOMINATIM_HEADERS, signal },
    );

    if (!response.ok) {
        return undefined;
    }

    const payload = (await response.json()) as { display_name?: string };

    return payload.display_name;
}

export async function searchPlaces(
    query: string,
    signal?: AbortSignal,
): Promise<NominatimPlace[]> {
    const trimmed = query.trim();

    if (trimmed.length < 3) {
        return [];
    }

    const params = new URLSearchParams({
        q: trimmed,
        format: 'json',
        addressdetails: '0',
        limit: '5',
    });

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
            (row): row is NominatimSearchRow & { display_name: string; lat: string; lon: string } =>
                Boolean(row.display_name && row.lat && row.lon),
        )
        .map((row) => ({
            displayName: row.display_name,
            latitude: row.lat,
            longitude: row.lon,
        }));
}
