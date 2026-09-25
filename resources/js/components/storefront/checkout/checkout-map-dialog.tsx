import { MapPin } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Pin = {
    latitude: string;
    longitude: string;
    address?: string;
};

type CheckoutMapDialogProps = {
    latitude: string;
    longitude: string;
    onSelect: (pin: Pin) => void;
};

const DEFAULT_LAT = -33.8688;
const DEFAULT_LNG = 151.2093;

function toNumber(value: string, fallback: number): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

async function reverseGeocode(
    lat: number,
    lng: number,
): Promise<string | undefined> {
    const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
        { headers: { Accept: 'application/json' } },
    );

    if (!response.ok) {
        return undefined;
    }

    const payload = (await response.json()) as { display_name?: string };

    return payload.display_name;
}

export function CheckoutMapDialog({
    latitude,
    longitude,
    onSelect,
}: CheckoutMapDialogProps) {
    const [open, setOpen] = useState(false);
    const [lat, setLat] = useState(latitude || String(DEFAULT_LAT));
    const [lng, setLng] = useState(longitude || String(DEFAULT_LNG));
    const [address, setAddress] = useState<string | undefined>();
    const mapElement = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<{
        remove: () => void;
        setView: (center: [number, number], zoom?: number) => void;
        getZoom: () => number;
    } | null>(null);
    const markerRef = useRef<{
        setLatLng: (latlng: [number, number]) => void;
    } | null>(null);

    useEffect(() => {
        if (!open || mapElement.current === null) {
            return;
        }

        let cancelled = false;

        void (async () => {
            const leaflet = await import('leaflet');

            if (cancelled || mapElement.current === null) {
                return;
            }

            const L = leaflet.default;
            const startLat = toNumber(lat, DEFAULT_LAT);
            const startLng = toNumber(lng, DEFAULT_LNG);
            const map = L.map(mapElement.current).setView(
                [startLat, startLng],
                13,
            );

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const marker = L.circleMarker([startLat, startLng], {
                radius: 8,
                color: '#111827',
                fillColor: '#111827',
                fillOpacity: 1,
            }).addTo(map);

            const dropPin = (nextLat: number, nextLng: number) => {
                setLat(String(nextLat));
                setLng(String(nextLng));
                marker.setLatLng([nextLat, nextLng]);
                map.setView([nextLat, nextLng], map.getZoom());
                void reverseGeocode(nextLat, nextLng).then((line) => {
                    if (line) {
                        setAddress(line);
                    }
                });
            };

            map.on(
                'click',
                (event: { latlng: { lat: number; lng: number } }) => {
                    dropPin(event.latlng.lat, event.latlng.lng);
                },
            );

            mapRef.current = map;
            markerRef.current = marker;
        })();

        return () => {
            cancelled = true;
            mapRef.current?.remove();
            mapRef.current = null;
            markerRef.current = null;
        };
    }, [open]);

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
                        Drop a pin on the map. OpenStreetMap is used; no Google
                        key is required.
                    </DialogDescription>
                </DialogHeader>
                <div
                    ref={mapElement}
                    className="h-64 w-full overflow-hidden rounded-md"
                />
                <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="checkout-lat">Latitude</Label>
                        <Input
                            id="checkout-lat"
                            value={lat}
                            onChange={(event) => setLat(event.target.value)}
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="checkout-lng">Longitude</Label>
                        <Input
                            id="checkout-lng"
                            value={lng}
                            onChange={(event) => setLng(event.target.value)}
                        />
                    </div>
                </div>
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
                        onClick={() => {
                            onSelect({
                                latitude: lat,
                                longitude: lng,
                                address,
                            });
                            setOpen(false);
                        }}
                    >
                        Use this pin
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
