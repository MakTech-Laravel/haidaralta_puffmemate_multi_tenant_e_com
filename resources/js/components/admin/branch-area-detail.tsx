import {
    Building2,
    CalendarDays,
    CircleDot,
    Hash,
    MapPin,
    StickyNote,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import {
    BranchLocationMapDialog,
    type BranchMapLinks,
} from '@/components/admin/branch-location-map-dialog';
import { AppDate } from '@/components/app-datetime';
import Heading from '@/components/heading';
import { StatCard } from '@/components/portal/stat-card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatAppDateTime } from '@/lib/datetime';

export type BranchAreaTransferRow = {
    id: number;
    from_branch_id: number;
    from_branch_name: string | null;
    to_branch_id: number;
    to_branch_name: string | null;
    effective_date: string | null;
    reason: string;
    transferred_by_name: string | null;
    transfer_type_label: string;
};

export type BranchAreaDetailData = {
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    map: BranchMapLinks | null;
    city: string;
    state: string;
    postal_code: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string | null;
    transfers?: BranchAreaTransferRow[];
};

type BranchAreaDetailProps = {
    area: BranchAreaDetailData;
    branchName?: string | null;
    actions?: ReactNode;
};

function DetailItem({
    label,
    value,
    icon,
}: {
    label: string;
    value: ReactNode;
    icon?: ReactNode;
}) {
    return (
        <div className="flex gap-3">
            {icon ? (
                <div className="bg-muted text-muted-foreground mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                    {icon}
                </div>
            ) : null}
            <div className="flex min-w-0 flex-col gap-1">
                <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                    {label}
                </dt>
                <dd className="text-sm font-medium wrap-break-word">{value}</dd>
            </div>
        </div>
    );
}

function formatCoord(value: string | null | undefined): string | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numeric = Number(value);

    if (Number.isFinite(numeric)) {
        return numeric.toFixed(7);
    }

    return value;
}

function displayValue(value: string | null | undefined): string {
    return value && value !== '' ? value : '—';
}

function coverageLabel(city: string, state: string): string {
    const parts = [city.trim(), state.trim()].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : '—';
}

function AreaTransferTimeline({
    transfers,
}: {
    transfers: BranchAreaTransferRow[];
}) {
    if (transfers.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No transfers recorded yet.
            </p>
        );
    }

    return (
        <div className="flex flex-col">
            {transfers.map((transfer, index) => (
                <div key={transfer.id}>
                    {index > 0 ? <Separator className="my-4" /> : null}
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                            <p className="text-sm font-medium">
                                {transfer.from_branch_name ?? '—'} →{' '}
                                {transfer.to_branch_name ?? '—'}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                <AppDate value={transfer.effective_date} />
                                {transfer.transferred_by_name
                                    ? ` · by ${transfer.transferred_by_name}`
                                    : ''}
                            </p>
                            {transfer.reason ? (
                                <p className="text-muted-foreground text-sm">
                                    {transfer.reason}
                                </p>
                            ) : null}
                        </div>
                        <Badge variant="secondary">
                            {transfer.transfer_type_label}
                        </Badge>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function BranchAreaDetail({
    area,
    branchName = null,
    actions,
}: BranchAreaDetailProps) {
    const [mapOpen, setMapOpen] = useState(false);
    const latitude = formatCoord(area.latitude);
    const longitude = formatCoord(area.longitude);
    const hasPin = area.map !== null && latitude !== null && longitude !== null;
    const createdLabel = formatAppDateTime(area.created_at);
    const transfers = area.transfers ?? [];
    const coverage = coverageLabel(area.city, area.state);
    const postalHint =
        area.postal_code && area.postal_code.trim() !== ''
            ? `Postal ${area.postal_code}`
            : 'Delivery zone coverage';
    const subtitle = branchName
        ? `Delivery zone for ${branchName}`
        : 'Delivery zone';
    const branchesLabel = branchName;

    return (
        <>
            <div className="flex w-full flex-col gap-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <Avatar className="size-14 rounded-2xl border shadow-sm">
                            <AvatarFallback className="bg-primary/10 text-primary rounded-2xl">
                                <MapPin className="size-6" aria-hidden />
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex min-w-0 flex-col gap-2">
                            <Heading title={area.name} description={subtitle} />
                            <Badge
                                variant={
                                    area.is_active ? 'default' : 'secondary'
                                }
                                className="w-fit"
                            >
                                {area.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                    </div>
                    {actions ? (
                        <div className="flex flex-wrap gap-2">{actions}</div>
                    ) : null}
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        icon={CircleDot}
                        label="Status"
                        value={area.is_active ? 'Active' : 'Inactive'}
                        hint="Whether this zone is available"
                    />
                    <StatCard
                        icon={Building2}
                        label="Coverage"
                        value={coverage}
                        hint={postalHint}
                    />
                    {hasPin ? (
                        <button
                            type="button"
                            className="focus-visible:ring-ring w-full cursor-pointer rounded-xl text-left focus-visible:ring-2 focus-visible:outline-none"
                            onClick={() => setMapOpen(true)}
                        >
                            <StatCard
                                icon={MapPin}
                                label="Location"
                                value="Pin set"
                                hint="View on map"
                            />
                        </button>
                    ) : (
                        <StatCard
                            icon={MapPin}
                            label="Location"
                            value="No pin"
                            hint="Add coordinates from Edit"
                        />
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                            <CardDescription>
                                Zone identity and coverage fields.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                {branchesLabel ? (
                                    <DetailItem
                                        label="Branches"
                                        value={branchesLabel}
                                        icon={<Building2 className="size-4" />}
                                    />
                                ) : null}
                                <DetailItem
                                    label="City"
                                    value={area.city}
                                    icon={<MapPin className="size-4" />}
                                />
                                <DetailItem
                                    label="State"
                                    value={area.state}
                                    icon={<MapPin className="size-4" />}
                                />
                                <DetailItem
                                    label="Postal code"
                                    value={displayValue(area.postal_code)}
                                    icon={<Hash className="size-4" />}
                                />
                                {createdLabel ? (
                                    <DetailItem
                                        label="Created"
                                        value={createdLabel}
                                        icon={
                                            <CalendarDays className="size-4" />
                                        }
                                    />
                                ) : null}
                                <div className="sm:col-span-2">
                                    <DetailItem
                                        label="Notes"
                                        value={displayValue(area.notes)}
                                        icon={<StickyNote className="size-4" />}
                                    />
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-2 space-y-0">
                            <div className="space-y-1.5">
                                <CardTitle>Location</CardTitle>
                                <CardDescription>
                                    Address, pin, and zone boundary preview.
                                </CardDescription>
                            </div>
                            {hasPin ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setMapOpen(true)}
                                >
                                    <MapPin className="size-4" />
                                    View pin
                                </Button>
                            ) : null}
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="space-y-1">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                    Address
                                </p>
                                <p className="text-sm font-medium wrap-break-word">
                                    {area.address}
                                </p>
                            </div>

                            <div className="text-muted-foreground flex flex-wrap gap-2 text-xs tabular-nums">
                                <span className="bg-muted rounded-md px-2 py-1">
                                    Lat {latitude ?? '—'}
                                </span>
                                <span className="bg-muted rounded-md px-2 py-1">
                                    Lng {longitude ?? '—'}
                                </span>
                            </div>

                            <div
                                className="relative h-56 overflow-hidden rounded-xl border"
                                aria-label="Area boundary map coming soon"
                            >
                                <div className="from-muted via-muted/80 to-muted absolute inset-0 bg-linear-to-br" />
                                <div
                                    className="absolute inset-0 opacity-40"
                                    style={{
                                        backgroundImage:
                                            'linear-gradient(to right, var(--border) 1px, transparent 1px), linear-gradient(to bottom, var(--border) 1px, transparent 1px)',
                                        backgroundSize: '28px 28px',
                                    }}
                                />
                                <svg
                                    className="absolute inset-0 size-full"
                                    viewBox="0 0 400 224"
                                    fill="none"
                                    aria-hidden
                                >
                                    <path
                                        d="M72 48 L168 36 L246 58 L318 44 L352 96 L330 156 L268 188 L180 198 L98 170 L58 112 Z"
                                        className="fill-primary/20 stroke-destructive"
                                        strokeWidth="3"
                                        strokeDasharray="10 6"
                                        strokeLinejoin="round"
                                    />
                                    <circle
                                        cx="210"
                                        cy="118"
                                        r="5"
                                        className="fill-primary stroke-background"
                                        strokeWidth="2"
                                    />
                                </svg>
                                <div className="bg-background/50 absolute inset-0" />
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 px-6 text-center">
                                    <span className="bg-background/95 text-foreground rounded-full border px-3 py-1 text-xs font-semibold tracking-wide uppercase shadow-sm">
                                        Coming soon
                                    </span>
                                    <p className="text-foreground text-sm font-medium">
                                        Selected area boundary
                                    </p>
                                    <p className="text-muted-foreground max-w-xs text-xs leading-relaxed">
                                        Draw and preview the delivery zone
                                        outline on the map in a future update.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Transfer history</CardTitle>
                        <CardDescription>
                            When this area was added to additional branches.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AreaTransferTimeline transfers={transfers} />
                    </CardContent>
                </Card>
            </div>

            {hasPin && area.map && latitude && longitude ? (
                <BranchLocationMapDialog
                    open={mapOpen}
                    onOpenChange={setMapOpen}
                    links={area.map}
                    latitude={latitude}
                    longitude={longitude}
                    name={area.name}
                    address={area.address}
                />
            ) : null}
        </>
    );
}
