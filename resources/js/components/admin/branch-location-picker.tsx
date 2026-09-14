import { MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

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

/**
 * Phase 1 shell: dialog UI for picking branch coordinates on a map.
 * Phase 2: replace the placeholder body with Mapbox/Google — keep this API.
 */
export function BranchLocationPicker({
    open,
    onOpenChange,
    initial = null,
    address,
    onConfirm,
}: BranchLocationPickerProps) {
    const trimmedAddress = address?.trim() ?? '';
    const hasInitial =
        Boolean(initial?.latitude?.trim()) &&
        Boolean(initial?.longitude?.trim());

    // Phase 1: confirm stays disabled until a map provider can produce a pin.
    const canConfirm = false;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Set location on map</DialogTitle>
                    <DialogDescription>
                        {trimmedAddress !== ''
                            ? `Near: ${trimmedAddress}`
                            : 'Pick a pin for this branch. You can also enter latitude and longitude on the form.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="border-border/70 bg-muted/30 text-muted-foreground flex min-h-56 flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-6 py-10 text-center">
                    <MapPin className="size-8 opacity-70" aria-hidden />
                    <div className="space-y-1">
                        <p className="text-foreground text-sm font-medium">
                            Map coming soon
                        </p>
                        <p className="text-xs leading-relaxed">
                            Mapbox or Google Maps will plug in here. Use the
                            latitude and longitude fields on the form until
                            then.
                        </p>
                    </div>
                    {hasInitial ? (
                        <p className="text-foreground/80 text-xs tabular-nums">
                            Current pin · {initial?.latitude},{' '}
                            {initial?.longitude}
                        </p>
                    ) : null}
                </div>

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
                        disabled={!canConfirm}
                        onClick={() => {
                            if (
                                !initial?.latitude?.trim() ||
                                !initial?.longitude?.trim()
                            ) {
                                return;
                            }

                            onConfirm({
                                latitude: initial.latitude,
                                longitude: initial.longitude,
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
