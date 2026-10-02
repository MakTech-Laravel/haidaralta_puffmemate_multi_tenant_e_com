import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
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
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

export type BranchAreaFormDefaults = {
    name?: string;
    address?: string;
    latitude?: string | number | null;
    longitude?: string | number | null;
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

    const { data, setData, post, processing, errors, setValidationTimeout } =
        useForm(formMethod, action.action, {
            ...(showBranchPicker ? { branch_id: initialBranchId } : {}),
            name: defaults?.name ?? '',
            address: defaults?.address ?? '',
            latitude: toCoordString(defaults?.latitude),
            longitude: toCoordString(defaults?.longitude),
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

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(action.action);
    };

    const hasPin = data.latitude.trim() !== '' && data.longitude.trim() !== '';
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
                    description="Search an address or drop a pin. City and state fill when available."
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
                                    placeholder="Start typing and select an address"
                                    onSelect={({
                                        address,
                                        latitude,
                                        longitude,
                                        city,
                                        state,
                                        postalCode,
                                    }) => {
                                        setData((current) => ({
                                            ...current,
                                            address,
                                            latitude,
                                            longitude,
                                            city: city ?? current.city,
                                            state: state ?? current.state,
                                            postal_code:
                                                postalCode ??
                                                current.postal_code,
                                        }));
                                    }}
                                    onClear={() => {
                                        setData((current) => ({
                                            ...current,
                                            address: '',
                                            latitude: '',
                                            longitude: '',
                                        }));
                                    }}
                                />
                            </div>
                            <CheckoutMapDialog
                                latitude={data.latitude}
                                longitude={data.longitude}
                                onSelect={({
                                    latitude,
                                    longitude,
                                    address,
                                    city,
                                    state,
                                    postalCode,
                                }) => {
                                    setData((current) => ({
                                        ...current,
                                        latitude,
                                        longitude,
                                        address: address || current.address,
                                        city: city ?? current.city,
                                        state: state ?? current.state,
                                        postal_code:
                                            postalCode ?? current.postal_code,
                                    }));
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
                                    Select an address from the suggestions or
                                    pin the location on the map.
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
