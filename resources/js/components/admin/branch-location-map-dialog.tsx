import { ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type BranchMapLinks = {
    google: string;
    open_street_map: string;
    embed: string;
};

type BranchLocationMapDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    links: BranchMapLinks;
    latitude: string;
    longitude: string;
    name?: string;
    address?: string | null;
};

export function BranchLocationMapDialog({
    open,
    onOpenChange,
    links,
    latitude,
    longitude,
    name,
    address,
}: BranchLocationMapDialogProps) {
    const trimmedAddress = address?.trim() ?? '';
    const title = name ? `${name} on the map` : 'Map location';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>
                        {trimmedAddress !== ''
                            ? trimmedAddress
                            : `${latitude}, ${longitude}`}
                    </DialogDescription>
                </DialogHeader>

                <div className="overflow-hidden rounded-xl border">
                    {open ? (
                        <iframe
                            title={title}
                            src={links.embed}
                            className="h-72 w-full md:h-80"
                            loading="lazy"
                            referrerPolicy="no-referrer-when-downgrade"
                        />
                    ) : null}
                </div>

                <p className="text-muted-foreground text-xs tabular-nums">
                    {latitude}, {longitude}
                </p>

                <DialogFooter className="gap-2 sm:justify-between">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                    >
                        Close
                    </Button>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={links.open_street_map}
                                target="_blank"
                                rel="noreferrer"
                            >
                                OpenStreetMap
                                <ExternalLink className="size-4" />
                            </a>
                        </Button>
                        <Button asChild>
                            <a
                                href={links.google}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Google Maps
                                <ExternalLink className="size-4" />
                            </a>
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
