import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import {
    BranchAreaPolygonMap,
    type PolygonPoint,
} from '@/components/admin/branch-area-polygon-map';
import InputError from '@/components/input-error';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { AddressSuggestInput } from '@/components/storefront/wholesale/address-suggest-input';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { resolveAreaZoneRing } from '@/lib/geo-polygon';
import { cn } from '@/lib/utils';

const AU_COUNTRY = 'au';

type FormAction = {
    action: string;
    method?: string;
};

export type BranchAreaFormDefaults = {
    name?: string;
    address?: string;
    latitude?: string | number | null;
    longitude?: string | number | null;
    polygon?: PolygonPoint[] | null;
    city?: string;
    state?: string;
    postal_code?: string | null;
    notes?: string | null;
    is_active?: boolean;
    branch_id?: string | number | null;
};

type BranchAreaFormProps = {
    action: FormAction;
    defaults?: BranchAreaFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
    /** When set, show a branch picker and include branch_id in the submit payload. */
    branchOptionsUrl?: string | null;
};

function RequiredMark() {
    return (
        <span className="text-primary" aria-hidden>
            *
        </span>
    );
}

function sectionClassName(): string {
    return cn(
        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
        'sm:p-6',
    );
}

function SectionHeader({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <div className="mb-5 space-y-1">
            <h2 className="text-base font-semibold tracking-tight">{title}</h2>
            <p className="text-muted-foreground text-sm">{description}</p>
        </div>
    );
}

function StatusCards({
    value,
    onChange,
}: {
    value: boolean;
    onChange: (active: boolean) => void;
}) {
    const options = [
        { active: true, label: 'Active' },
        { active: false, label: 'Inactive' },
    ] as const;

    return (
        <div
            role="radiogroup"
            aria-label="Area status"
            className="grid grid-cols-2 gap-3"
        >
            {options.map((option) => {
                const selected = value === option.active;

                return (
                    <button
                        key={option.label}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        onClick={() => onChange(option.active)}
                        className={cn(
                            'flex min-h-11 items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left text-sm font-medium transition-colors',
                            selected
                                ? 'border-primary bg-primary/5 ring-primary/20 ring-1'
                                : 'border-border/80 bg-card hover:bg-muted/40',
                        )}
                    >
                        <span
                            className={cn(
                                'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                selected
                                    ? 'border-primary'
                                    : 'border-muted-foreground/40',
                            )}
                            aria-hidden
                        >
                            {selected ? (
                                <span className="bg-primary size-2 rounded-full" />
                            ) : null}
                        </span>
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

function toCoordString(value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
}

function formatCoord(value: string): string {
    const numeric = Number(value);

    return Number.isFinite(numeric) ? numeric.toFixed(7) : value;
}

function toPolygonPoints(
    value: PolygonPoint[] | null | undefined,
): PolygonPoint[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .filter(
            (point): point is PolygonPoint =>
                point !== null &&
                typeof point === 'object' &&
                Number.isFinite(Number(point.lat)) &&
                Number.isFinite(Number(point.lng)),
        )
        .map((point) => ({
            lat: Number(point.lat),
            lng: Number(point.lng),
        }));
}

function polygonVertexCount(ring: PolygonPoint[]): number {
    if (ring.length === 0) {
        return 0;
    }

    const first = ring[0];
    const last = ring[ring.length - 1];

    if (
        ring.length > 1 &&
        first.lat === last.lat &&
        first.lng === last.lng
    ) {
        return ring.length - 1;
    }

    return ring.length;
}

export function BranchAreaForm({
    action,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
    branchOptionsUrl = null,
}: BranchAreaFormProps) {
    const formMethod = (action.method ?? 'post').toLowerCase();
    const showBranchPicker = Boolean(branchOptionsUrl);
    const initialBranchId =
        defaults?.branch_id !== null && defaults?.branch_id !== undefined
            ? String(defaults.branch_id)
            : '';
    const [branchLabel, setBranchLabel] = useState<string | null>(null);
    const [zoneResolveFailed, setZoneResolveFailed] = useState(false);
    const zoneAbortRef = useRef<AbortController | null>(null);

    const { data, setData, post, processing, errors, setValidationTimeout } =
        useForm(formMethod, action.action, {
            ...(showBranchPicker ? { branch_id: initialBranchId } : {}),
            name: defaults?.name ?? '',
            address: defaults?.address ?? '',
            latitude: toCoordString(defaults?.latitude),
            longitude: toCoordString(defaults?.longitude),
            polygon: toPolygonPoints(defaults?.polygon),
            city: defaults?.city ?? '',
            state: defaults?.state ?? '',
            postal_code: defaults?.postal_code ?? '',
            notes: defaults?.notes ?? '',
            is_active: defaults?.is_active ?? true,
            ...(isEdit ? { _method: 'put' } : {}),
        });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    useEffect(() => {
        return () => {
            zoneAbortRef.current?.abort();
        };
    }, []);

    const applyLocationAndZone = (input: {
        address: string;
        latitude: string;
        longitude: string;
        city?: string;
        state?: string;
        postalCode?: string;
    }): void => {
        setData((current) => ({
            ...current,
            address: input.address,
            latitude: input.latitude,
            longitude: input.longitude,
            city: input.city ?? current.city,
            state: input.state ?? current.state,
            postal_code: input.postalCode ?? current.postal_code,
        }));
        setZoneResolveFailed(false);

        const lat = Number(input.latitude);
        const lng = Number(input.longitude);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            setZoneResolveFailed(true);

            return;
        }

        zoneAbortRef.current?.abort();
        const controller = new AbortController();
        zoneAbortRef.current = controller;

        void resolveAreaZoneRing({
            query: input.address,
            lat,
            lng,
            countrycodes: AU_COUNTRY,
            signal: controller.signal,
        })
            .then((ring) => {
                if (controller.signal.aborted) {
                    return;
                }

                if (ring === null || polygonVertexCount(ring) < 3) {
                    setZoneResolveFailed(true);
                    setData('polygon', []);

                    return;
                }

                setZoneResolveFailed(false);
                setData('polygon', ring);
            })
            .catch((err: unknown) => {
                if (err instanceof DOMException && err.name === 'AbortError') {
                    return;
                }

                setZoneResolveFailed(true);
            });
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(action.action);
    };

    const hasPin = data.latitude.trim() !== '' && data.longitude.trim() !== '';
    const hasPolygon = polygonVertexCount(data.polygon) >= 3;
    const branchId =
        'branch_id' in data && typeof data.branch_id === 'string'
            ? data.branch_id
            : '';

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            {showBranchPicker && branchOptionsUrl ? (
                <section className={sectionClassName()}>
                    <SectionHeader
                        title="Branch"
                        description="Choose which store this delivery zone belongs to."
                    />
                    <div className="space-y-2">
                        <Label htmlFor="area-branch-id">
                            Branch <RequiredMark />
                        </Label>
                        <InfiniteScrollCombobox
                            id="area-branch-id"
                            optionsUrl={branchOptionsUrl}
                            value={branchId || null}
                            onValueChange={(next, option) => {
                                setData('branch_id', next ?? '');
                                setBranchLabel(option?.label ?? null);
                            }}
                            selectedLabel={branchLabel}
                            placeholder="Select a branch"
                            searchPlaceholder="Search branches…"
                            emptyText="No branches found."
                            descriptionKey={null}
                            className={
                                errors.branch_id
                                    ? 'border-destructive'
                                    : undefined
                            }
                        />
                        <InputError message={errors.branch_id} />
                    </div>
                </section>
            ) : null}

            <section className={sectionClassName()}>
                <SectionHeader
                    title="Identity"
                    description="Name and whether this zone is available for delivery."
                />
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="area-name">
                            Name <RequiredMark />
                        </Label>
                        <Input
                            id="area-name"
                            name="name"
                            value={data.name}
                            placeholder="Surry Hills"
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <StatusCards
                            value={data.is_active}
                            onChange={(active) => setData('is_active', active)}
                        />
                        <InputError message={errors.is_active} />
                    </div>
                </div>
            </section>

            <section className={sectionClassName()}>
                <SectionHeader
                    title="Location"
                    description="Search an Australian address or drop a pin. The zone outline follows the address."
                />
                <div className="flex flex-col gap-5">
                    <div className="space-y-2">
                        <div className="flex items-end gap-2">
                            <div className="min-w-0 flex-1 space-y-2">
                                <Label htmlFor="area-address">
                                    Address <RequiredMark />
                                </Label>
                                <AddressSuggestInput
                                    id="area-address"
                                    name="address"
                                    value={data.address}
                                    countrycodes={AU_COUNTRY}
                                    placeholder="Start typing an Australian address"
                                    onSelect={({
                                        address,
                                        latitude,
                                        longitude,
                                        city,
                                        state,
                                        postalCode,
                                    }) => {
                                        applyLocationAndZone({
                                            address,
                                            latitude,
                                            longitude,
                                            city,
                                            state,
                                            postalCode,
                                        });
                                    }}
                                    onClear={() => {
                                        zoneAbortRef.current?.abort();
                                        setZoneResolveFailed(false);
                                        setData((current) => ({
                                            ...current,
                                            address: '',
                                            latitude: '',
                                            longitude: '',
                                            polygon: [],
                                        }));
                                    }}
                                />
                            </div>
                            <CheckoutMapDialog
                                latitude={data.latitude}
                                longitude={data.longitude}
                                countrycodes={AU_COUNTRY}
                                onSelect={({
                                    latitude,
                                    longitude,
                                    address,
                                    city,
                                    state,
                                    postalCode,
                                }) => {
                                    applyLocationAndZone({
                                        address:
                                            address ||
                                            data.address ||
                                            `${latitude}, ${longitude}`,
                                        latitude,
                                        longitude,
                                        city,
                                        state,
                                        postalCode,
                                    });
                                }}
                            />
                        </div>
                        <InputError message={errors.address} />
                        <InputError message={errors.latitude} />
                        <InputError message={errors.longitude} />
                        {hasPin ? (
                            <div className="text-muted-foreground flex flex-wrap gap-2 text-xs tabular-nums">
                                <span className="bg-muted rounded-md px-2 py-1">
                                    Lat {formatCoord(data.latitude)}
                                </span>
                                <span className="bg-muted rounded-md px-2 py-1">
                                    Lng {formatCoord(data.longitude)}
                                </span>
                            </div>
                        ) : (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    Select an Australian address from the
                                    suggestions or pin the location on the map.
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>

                    <div className="grid gap-5 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="area-city">
                                City <RequiredMark />
                            </Label>
                            <Input
                                id="area-city"
                                name="city"
                                value={data.city}
                                placeholder="Sydney"
                                onChange={(event) =>
                                    setData('city', event.target.value)
                                }
                            />
                            <InputError message={errors.city} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="area-state">
                                State <RequiredMark />
                            </Label>
                            <Input
                                id="area-state"
                                name="state"
                                value={data.state}
                                placeholder="NSW"
                                onChange={(event) =>
                                    setData('state', event.target.value)
                                }
                            />
                            <InputError message={errors.state} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="area-postal-code">
                                Postal code{' '}
                                <span className="text-muted-foreground font-normal">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="area-postal-code"
                                name="postal_code"
                                value={data.postal_code}
                                placeholder="2010"
                                onChange={(event) =>
                                    setData('postal_code', event.target.value)
                                }
                            />
                            <InputError message={errors.postal_code} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <p className="text-muted-foreground text-xs">
                            Zone outline follows the address.
                        </p>
                        <BranchAreaPolygonMap
                            value={data.polygon}
                            onChange={(ring) => setData('polygon', ring)}
                            centerLat={data.latitude}
                            centerLng={data.longitude}
                        />
                        <InputError message={errors.polygon} />
                        {hasPin && !hasPolygon ? (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    {zoneResolveFailed
                                        ? 'Could not build a zone outline for this address. Try another Australian address or draw on the map.'
                                        : 'Waiting for zone outline…'}
                                </AlertDescription>
                            </Alert>
                        ) : null}
                    </div>
                </div>
            </section>

            <section className={sectionClassName()}>
                <SectionHeader
                    title="Notes"
                    description="Optional delivery or coverage notes for staff."
                />
                <div className="space-y-2">
                    <Label htmlFor="area-notes">Notes</Label>
                    <Textarea
                        id="area-notes"
                        name="notes"
                        rows={3}
                        value={data.notes}
                        placeholder="Delivery notes for this area"
                        onChange={(event) =>
                            setData('notes', event.target.value)
                        }
                    />
                    <InputError message={errors.notes} />
                </div>
            </section>

            <div className="flex flex-wrap items-center gap-3">
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ?? (isEdit ? 'Save changes' : 'Create area')}
                </Button>
                <Button type="button" variant="outline" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
