import { MapPin } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { MapView } from '@/components/map/map-view';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { MarkerSpec } from '@/lib/maps/types';
import { reverseGeocodeDetails } from '@/lib/nominatim';

type Pin = {
    latitude: string;
    longitude: string;
    address?: string;
    city?: string;
    state?: string;
    postalCode?: string;
};

type CheckoutMapDialogProps = {
    latitude: string;
    longitude: string;
    onSelect: (pin: Pin) => void;
    /** When set (e.g. "au"), pins outside that country are rejected. */
    countrycodes?: string;
};

const DEFAULT_LAT = -33.8688;
const DEFAULT_LNG = 151.2093;

function toNumber(value: string, fallback: number): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

export function CheckoutMapDialog({
    latitude,
    longitude,
    onSelect,
    countrycodes,
}: CheckoutMapDialogProps) {
    const [open, setOpen] = useState(false);
    const [lat, setLat] = useState(latitude || String(DEFAULT_LAT));
    const [lng, setLng] = useState(longitude || String(DEFAULT_LNG));
    const [address, setAddress] = useState<string | undefined>();
    const [city, setCity] = useState<string | undefined>();
    const [state, setState] = useState<string | undefined>();
    const [postalCode, setPostalCode] = useState<string | undefined>();
    const [locationError, setLocationError] = useState<string | null>(null);
    // Keep local fields in sync with parent pin (e.g. address suggest) while closed.
    useEffect(() => {
        if (open) {
            return;
        }

        setLat(latitude.trim() !== '' ? latitude : String(DEFAULT_LAT));
        setLng(longitude.trim() !== '' ? longitude : String(DEFAULT_LNG));
        setAddress(undefined);
        setCity(undefined);
        setState(undefined);
        setPostalCode(undefined);
        setLocationError(null);
    }, [latitude, longitude, open]);

    const dropPin = useCallback(
        (nextLat: number, nextLng: number) => {
            setLat(String(nextLat));
            setLng(String(nextLng));
            setLocationError(null);

            void reverseGeocodeDetails(nextLat, nextLng, undefined, {
                countrycodes,
            }).then((result) => {
                if (!result) {
                    setAddress(undefined);
                    setCity(undefined);
                    setState(undefined);
                    setPostalCode(undefined);

                    if (countrycodes === 'au') {
                        setLocationError(
                            'Only Australian locations can be selected.',
                        );
                    }

                    return;
                }

                setAddress(result.address);
                setCity(result.city);
                setState(result.state);
                setPostalCode(result.postalCode);
                setLocationError(null);
            });
        },
        [countrycodes],
    );

    const pinLat = toNumber(lat, DEFAULT_LAT);
    const pinLng = toNumber(lng, DEFAULT_LNG);
    const pinMarkers = useMemo<MarkerSpec[]>(
        () => [{ id: 'pin', lat: pinLat, lng: pinLng, kind: 'pin' }],
        [pinLat, pinLng],
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    aria-label="Pick location on map"
                >
                    <MapPin className="size-4" />
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Select location</DialogTitle>
                    <DialogDescription>
                        {countrycodes === 'au'
                            ? 'Drop a pin in Australia.'
                            : 'Drop a pin on the map.'}
                    </DialogDescription>
                </DialogHeader>
                <MapView
                    center={[pinLat, pinLng]}
                    centerKey={`${pinLat},${pinLng}`}
                    markers={pinMarkers}
                    onMapClick={dropPin}
                    className="h-64 w-full cursor-pointer! overflow-hidden rounded-xl **:cursor-pointer!"
                />
                {locationError ? (
                    <p className="text-destructive text-sm">{locationError}</p>
                ) : null}
                {address ? (
                    <p className="text-muted-foreground text-sm">{address}</p>
                ) : null}
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            if (!navigator.geolocation) {
                                return;
                            }

                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    setLat(String(position.coords.latitude));
                                    setLng(String(position.coords.longitude));
                                },
                            );
                        }}
                    >
                        Use my location
                    </Button>
                    <Button
                        type="button"
                        disabled={Boolean(locationError)}
                        onClick={() => {
                            const nextLat = Number(lat);
                            const nextLng = Number(lng);

                            if (
                                !Number.isFinite(nextLat) ||
                                !Number.isFinite(nextLng)
                            ) {
                                return;
                            }

                            void (async () => {
                                if (countrycodes) {
                                    const result = await reverseGeocodeDetails(
                                        nextLat,
                                        nextLng,
                                        undefined,
                                        { countrycodes },
                                    );

                                    if (!result) {
                                        setLocationError(
                                            countrycodes === 'au'
                                                ? 'Only Australian locations can be selected.'
                                                : 'Location is outside the allowed country.',
                                        );

                                        return;
                                    }

                                    onSelect({
                                        latitude: String(nextLat),
                                        longitude: String(nextLng),
                                        address: result.address,
                                        city: result.city,
                                        state: result.state,
                                        postalCode: result.postalCode,
                                    });
                                    setOpen(false);

                                    return;
                                }

                                onSelect({
                                    latitude: String(nextLat),
                                    longitude: String(nextLng),
                                    address,
                                    city,
                                    state,
                                    postalCode,
                                });
                                setOpen(false);
                            })();
                        }}
                    >
                        Use this pin
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
