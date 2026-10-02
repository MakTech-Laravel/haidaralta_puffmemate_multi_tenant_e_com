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

export type NominatimReverseResult = {
    address: string;
} & NominatimAddressParts;

type NominatimAddress = {
    city?: string;
    town?: string;
    village?: string;
    suburb?: string;
    municipality?: string;
    state?: string;
    postcode?: string;
};

type NominatimSearchRow = {
    display_name?: string;
    lat?: string;
    lon?: string;
    address?: NominatimAddress;
};

type NominatimReverseRow = {
    display_name?: string;
    address?: NominatimAddress;
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

export async function reverseGeocode(
    lat: number,
    lng: number,
    signal?: AbortSignal,
): Promise<string | undefined> {
    const result = await reverseGeocodeDetails(lat, lng, signal);

    return result?.address;
}

export async function reverseGeocodeDetails(
    lat: number,
    lng: number,
    signal?: AbortSignal,
): Promise<NominatimReverseResult | undefined> {
    const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`,
        { headers: NOMINATIM_HEADERS, signal },
    );

    if (!response.ok) {
        return undefined;
    }

    const payload = (await response.json()) as NominatimReverseRow;

    if (!payload.display_name) {
        return undefined;
    }

    return {
        address: payload.display_name,
        ...addressParts(payload.address),
    };
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
        addressdetails: '1',
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
            ...addressParts(row.address),
        }));
}
