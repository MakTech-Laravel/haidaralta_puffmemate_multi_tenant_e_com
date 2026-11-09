import { useEffect, useMemo, useState } from 'react';
import { MapView } from '@/components/map/map-view';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { LatLngTuple, MarkerSpec } from '@/lib/maps/types';

export type BranchLocationCoords = {
    latitude: string;
    longitude: string;
};

type BranchLocationPickerProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initial?: BranchLocationCoords | null;
    address?: string;
    onConfirm: (coords: BranchLocationCoords) => void;
};

// Centre of Australia, used until a pin exists.
const DEFAULT_CENTER: LatLngTuple = [-25.2744, 133.7751];

function parsePin(coords?: BranchLocationCoords | null): LatLngTuple | null {
    const lat = Number(coords?.latitude);
    const lng = Number(coords?.longitude);

    return coords?.latitude?.trim() &&
        coords?.longitude?.trim() &&
        Number.isFinite(lat) &&
        Number.isFinite(lng)
        ? [lat, lng]
        : null;
}

/**
 * Dialog for picking a branch's coordinates on the active map provider.
 * Click the map to drop or move the pin, then confirm.
 */
export function BranchLocationPicker({
    open,
    onOpenChange,
    initial = null,
    address,
    onConfirm,
}: BranchLocationPickerProps) {
    const trimmedAddress = address?.trim() ?? '';
    const [pin, setPin] = useState<LatLngTuple | null>(() => parsePin(initial));

    useEffect(() => {
        if (open) {
            setPin(parsePin(initial));
        }
    }, [open, initial]);

    const markers = useMemo<MarkerSpec[]>(
        () =>
            pin ? [{ id: 'pin', lat: pin[0], lng: pin[1], kind: 'pin' }] : [],
        [pin],
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Set location on map</DialogTitle>
                    <DialogDescription>
                        {trimmedAddress !== ''
                            ? `Near: ${trimmedAddress}. Click the map to place the pin.`
                            : 'Click the map to place the pin for this branch.'}
                    </DialogDescription>
                </DialogHeader>

                <MapView
                    center={pin ?? DEFAULT_CENTER}
                    zoom={pin ? 15 : 4}
                    centerKey={pin ? `${pin[0]},${pin[1]}` : undefined}
                    markers={markers}
                    onMapClick={(lat, lng) =>
                        setPin([Number(lat.toFixed(7)), Number(lng.toFixed(7))])
                    }
                    className="h-72 w-full overflow-hidden rounded-xl"
                />

                <p className="text-muted-foreground text-xs tabular-nums">
                    {pin
                        ? `Pin · ${pin[0]}, ${pin[1]}`
                        : 'No pin yet. Click the map to set one.'}
                </p>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        disabled={pin === null}
                        onClick={() => {
                            if (pin === null) {
                                return;
                            }

                            onConfirm({
                                latitude: String(pin[0]),
                                longitude: String(pin[1]),
                            });
                            onOpenChange(false);
                        }}
                    >
                        Use this location
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
