import { Link, useForm } from '@inertiajs/react';
import { MapPin, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import {
    BranchLocationPicker,
    type BranchLocationCoords,
} from '@/components/admin/branch-location-picker';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { TimeSelect } from '@/components/ui/time-select';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type AreaOption = {
    id: number;
    name: string;
    branch_names: string[];
};

interface BranchFormDefaults {
    name?: string;
    slug?: string;
    code?: string;
    logo?: string | null;
    logo_url?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
    latitude?: string | number | null;
    longitude?: string | number | null;
    delivery_radius_km?: string | number | null;
    opening_time?: string | null;
    closing_time?: string | null;
    status?: boolean;
    area_ids?: number[];
}

interface BranchFormProps {
    action: FormAction;
    defaults?: BranchFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
    /** When true (create only), require first branch super-admin in the same submit. */
    includeSuperAdmin?: boolean;
    /** Hide slug, code, and Active — branch portal settings keep identity platform-owned. */
    hideIdentityFields?: boolean;
    /** Platform create/edit only — optional multi-select of existing areas. */
    areaOptions?: AreaOption[];
}

const LOGO_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

type FormErrors = Record<string, string | undefined>;

/** Only collected while creating a branch, never while editing one. */
type SuperAdminFields = {
    name: string;
    phone: string;
    email: string;
    password: string;
    password_confirmation: string;
};

const SUPER_ADMIN_DEFAULTS: SuperAdminFields = {
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
};

function fieldError(errors: FormErrors, key: string): string | undefined {
    return errors[key];
}

export function BranchForm({
    action,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
    includeSuperAdmin = false,
    hideIdentityFields = false,
    areaOptions = [],
}: BranchFormProps) {
    const showAreaAssign = !hideIdentityFields && areaOptions.length > 0;

    const [areaSearch, setAreaSearch] = useState('');
    const filteredAreas = useMemo(() => {
        const q = areaSearch.trim().toLowerCase();

        if (q === '') {
            return areaOptions;
        }

        return areaOptions.filter(
            (area) =>
                area.name.toLowerCase().includes(q) ||
                area.branch_names.some((name) =>
                    name.toLowerCase().includes(q),
                ),
        );
    }, [areaOptions, areaSearch]);

    const [slugTouched, setSlugTouched] = useState(isEdit || hideIdentityFields);
    const [mapPickerOpen, setMapPickerOpen] = useState(false);
    const [existingLogo, setExistingLogo] = useState<ExistingFile[]>(() =>
        defaults?.logo && defaults?.logo_url
            ? [
                  {
                      id: 'logo',
                      path: defaults.logo,
                      url: defaults.logo_url,
                      mime_type: 'image/jpeg',
                      name: defaults.logo.split('/').pop() ?? 'branch-logo',
                  },
              ]
            : [],
    );

    const formMethod = (action.method ?? 'post').toLowerCase() as
        | 'get'
        | 'post'
        | 'put'
        | 'patch'
        | 'delete';

    const {
        data,
        setData,
        post,
        processing,
        errors,
        progress,
        transform,
        validate,
        setValidationTimeout,
        cancel,
    } = useForm(formMethod, action.action, {
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        code: defaults?.code ?? '',
        logo: null as File | null,
        remove_logo: false,
        phone: defaults?.phone ?? '',
        email: defaults?.email ?? '',
        address: defaults?.address ?? '',
        latitude:
            defaults?.latitude === null || defaults?.latitude === undefined
                ? ''
                : String(defaults.latitude),
        longitude:
            defaults?.longitude === null || defaults?.longitude === undefined
                ? ''
                : String(defaults.longitude),
        delivery_radius_km:
            defaults?.delivery_radius_km === null ||
            defaults?.delivery_radius_km === undefined
                ? ''
                : String(defaults.delivery_radius_km),
        opening_time: defaults?.opening_time ?? '',
        closing_time: defaults?.closing_time ?? '',
        status: defaults?.status ?? true,
        ...(!hideIdentityFields
            ? { area_ids: defaults?.area_ids ?? [] }
            : {}),
        ...(includeSuperAdmin
            ? { super_admin: { ...SUPER_ADMIN_DEFAULTS } }
            : {}),
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const formErrors = errors as FormErrors;

    /**
     * `super_admin` is only present when creating a branch, so the conditional
     * spread above leaves it optional. Reading through a defaulted copy keeps
     * every field a plain string for the controlled inputs below.
     */
    const superAdmin: SuperAdminFields =
        'super_admin' in data && data.super_admin
            ? { ...SUPER_ADMIN_DEFAULTS, ...data.super_admin }
            : SUPER_ADMIN_DEFAULTS;

    const setSuperAdmin = (patch: Partial<SuperAdminFields>): void => {
        setData('super_admin', { ...superAdmin, ...patch });
    };

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    useEffect(() => {
        if (slugTouched) {
            return;
        }

        const nextSlug = slugify(data.name);

        if (nextSlug !== data.slug) {
            setData('slug', nextSlug);
        }
    }, [data.name, data.slug, slugTouched, setData]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const {
                logo,
                remove_logo,
                delivery_radius_km,
                slug,
                code,
                status,
                ...rest
            } = form;

            return {
                ...rest,
                ...(hideIdentityFields ? {} : { slug, code, status }),
                delivery_radius_km:
                    delivery_radius_km === '' ? null : delivery_radius_km,
                ...(logo ? { logo } : {}),
                ...(remove_logo ? { remove_logo: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <section
                className={cn(
                    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
                    'sm:p-6',
                )}
            >
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Branch details
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Name, contact details, hours, and logo for this
                        location.
                    </p>
                </div>

                <div className="flex flex-col gap-4">
                    <div
                        className={cn(
                            'flex gap-4',
                            hideIdentityFields
                                ? 'items-start'
                                : 'items-stretch',
                        )}
                    >
                        <div
                            className={cn(
                                'flex shrink-0 flex-col gap-1.5',
                                hideIdentityFields
                                    ? 'w-32 sm:w-36'
                                    : 'w-40 self-stretch sm:w-44',
                            )}
                        >
                            <Label>Logo</Label>
                            <div
                                className={cn(
                                    'flex flex-col overflow-hidden',
                                    hideIdentityFields
                                        ? 'h-32 sm:h-36'
                                        : 'min-h-44 flex-1',
                                )}
                            >
                                <FileUpload
                                    value={data.logo}
                                    onChange={(file) => {
                                        const next = Array.isArray(file)
                                            ? (file[0] ?? null)
                                            : file;
                                        setExistingLogo([]);
                                        setData((current) => ({
                                            ...current,
                                            logo: next,
                                            remove_logo: next
                                                ? false
                                                : current.remove_logo,
                                        }));
                                    }}
                                    existingFiles={
                                        data.logo || data.remove_logo
                                            ? []
                                            : existingLogo
                                    }
                                    onRemoveExisting={() => {
                                        setExistingLogo([]);
                                        setData((current) => ({
                                            ...current,
                                            logo: null,
                                            remove_logo: true,
                                        }));
                                    }}
                                    accept={LOGO_ACCEPT}
                                    maxSize={2}
                                    maxFiles={1}
                                    multiple={false}
                                    lightbox
                                    compactCards={hideIdentityFields}
                                    isUploading={processing}
                                    uploadProgress={
                                        progress?.percentage ?? null
                                    }
                                    onCancel={processing ? cancel : undefined}
                                    placeholder="Add a logo"
                                    hint="PNG, JPG, or WebP · up to 2MB"
                                    error={errors.logo}
                                    classNames={{
                                        wrapper: cn(
                                            'flex w-full flex-col',
                                            hideIdentityFields
                                                ? 'h-full'
                                                : 'h-full min-h-44 flex-1',
                                        ),
                                        dropzone: cn(
                                            'w-full border-dashed border-border/60 bg-muted/30 px-3 py-4',
                                            hideIdentityFields
                                                ? 'flex h-full items-center justify-center'
                                                : 'flex h-full min-h-44 w-full flex-1',
                                        ),
                                        dropzoneContent: 'gap-1.5',
                                        card: cn(
                                            'flex w-full flex-col overflow-hidden border-0 bg-transparent shadow-none hover:shadow-none',
                                            hideIdentityFields
                                                ? 'h-full'
                                                : 'h-full',
                                        ),
                                        cardMedia: cn(
                                            'bg-transparent',
                                            hideIdentityFields
                                                ? 'aspect-square flex-1'
                                                : 'aspect-auto min-h-44 flex-1',
                                        ),
                                        cardImage: cn(
                                            'size-full object-center',
                                            hideIdentityFields
                                                ? 'object-contain p-2'
                                                : 'object-cover',
                                        ),
                                        cardInfo:
                                            'border-border/50 border-t bg-background/70',
                                    }}
                                />
                            </div>
                        </div>

                        <div className="flex min-w-0 flex-1 flex-col gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="name">Branch name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    required
                                    placeholder="PuffMeMate Sydney CBD"
                                    autoComplete="off"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {!hideIdentityFields ? (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="slug">URL name</Label>
                                        <Input
                                            id="slug"
                                            value={data.slug}
                                            onChange={(event) => {
                                                setSlugTouched(true);
                                                setData(
                                                    'slug',
                                                    event.target.value,
                                                );
                                            }}
                                            onBlur={() => validate('slug')}
                                            required
                                            placeholder="sydney-cbd"
                                        />
                                        <InputError message={errors.slug} />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="code">
                                            Short code
                                        </Label>
                                        <Input
                                            id="code"
                                            value={data.code}
                                            onChange={(event) =>
                                                setData(
                                                    'code',
                                                    event.target.value,
                                                )
                                            }
                                            onBlur={() => validate('code')}
                                            required
                                            placeholder="SYD"
                                        />
                                        <InputError message={errors.code} />
                                    </div>
                                </div>
                            ) : null}

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(event) =>
                                            setData('phone', event.target.value)
                                        }
                                        onBlur={() => {
                                            if (data.phone.trim() !== '') {
                                                validate('phone');
                                            }
                                        }}
                                        placeholder="02 9876 5432"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="email">
                                        Email{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (optional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData('email', event.target.value)
                                        }
                                        onBlur={() => {
                                            if (data.email.trim() !== '') {
                                                validate('email');
                                            }
                                        }}
                                        placeholder="sydney@puffmemate.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="address">Address</Label>
                        <Textarea
                            id="address"
                            value={data.address}
                            onChange={(event) =>
                                setData('address', event.target.value)
                            }
                            placeholder="Street, suburb, city"
                            rows={3}
                        />
                        <InputError message={errors.address} />
                    </div>

                    <div className="flex flex-col gap-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <p className="text-sm leading-none font-medium">
                                    Map location
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    Optional. Pick a pin on the map, or enter
                                    coordinates manually.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full shrink-0 sm:w-auto"
                                onClick={() => setMapPickerOpen(true)}
                            >
                                <MapPin className="size-4" />
                                Set on map
                            </Button>
                        </div>

                        {data.latitude.trim() !== '' &&
                        data.longitude.trim() !== '' ? (
                            <p className="text-muted-foreground text-xs tabular-nums">
                                Pin set · {data.latitude}, {data.longitude}
                            </p>
                        ) : null}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="latitude">
                                    Latitude{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="latitude"
                                    type="number"
                                    step="any"
                                    value={data.latitude}
                                    onChange={(event) =>
                                        setData('latitude', event.target.value)
                                    }
                                    placeholder="-33.8688"
                                />
                                <InputError message={errors.latitude} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="longitude">
                                    Longitude{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="longitude"
                                    type="number"
                                    step="any"
                                    value={data.longitude}
                                    onChange={(event) =>
                                        setData('longitude', event.target.value)
                                    }
                                    placeholder="151.2093"
                                />
                                <InputError message={errors.longitude} />
                            </div>
                        </div>
                    </div>

                    <BranchLocationPicker
                        open={mapPickerOpen}
                        onOpenChange={setMapPickerOpen}
                        address={data.address}
                        initial={
                            data.latitude.trim() !== '' &&
                            data.longitude.trim() !== ''
                                ? {
                                      latitude: data.latitude,
                                      longitude: data.longitude,
                                  }
                                : null
                        }
                        onConfirm={(coords: BranchLocationCoords) => {
                            setData((current) => ({
                                ...current,
                                latitude: coords.latitude,
                                longitude: coords.longitude,
                            }));
                        }}
                    />

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="delivery_radius_km">
                                Delivery radius{' '}
                                <span className="text-muted-foreground font-normal">
                                    (km, optional)
                                </span>
                            </Label>
                            <Input
                                id="delivery_radius_km"
                                type="number"
                                step="0.1"
                                min={0.1}
                                value={data.delivery_radius_km}
                                onChange={(event) =>
                                    setData(
                                        'delivery_radius_km',
                                        event.target.value,
                                    )
                                }
                                placeholder="e.g. 15"
                            />
                            <p className="text-muted-foreground text-xs">
                                Empty = deliver anywhere.
                            </p>
                            <InputError message={errors.delivery_radius_km} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="opening_time">Opens</Label>
                            <TimeSelect
                                id="opening_time"
                                value={data.opening_time}
                                onChange={(value) =>
                                    setData('opening_time', value)
                                }
                                placeholder="Opening time"
                                aria-invalid={Boolean(errors.opening_time)}
                            />
                            <InputError message={errors.opening_time} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="closing_time">Closes</Label>
                            <TimeSelect
                                id="closing_time"
                                value={data.closing_time}
                                onChange={(value) =>
                                    setData('closing_time', value)
                                }
                                placeholder="Closing time"
                                aria-invalid={Boolean(errors.closing_time)}
                            />
                            <InputError message={errors.closing_time} />
                        </div>
                    </div>

                    {!hideIdentityFields ? (
                        <div className="flex items-center gap-2 pt-1">
                            <Checkbox
                                id="status"
                                checked={data.status}
                                onCheckedChange={(value) =>
                                    setData('status', value === true)
                                }
                            />
                            <Label htmlFor="status" className="font-normal">
                                Branch is active
                            </Label>
                            <InputError message={errors.status} />
                        </div>
                    ) : null}
                </div>
            </section>

            {showAreaAssign ? (
                <section
                    className={cn(
                        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Service areas
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Optional. Link existing areas to this branch. An
                            area can belong to many branches — selecting does
                            not remove it from others. Deselecting does not
                            unlink an area already on this branch.
                        </p>
                    </div>

                    <div className="relative mb-3">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            value={areaSearch}
                            onChange={(event) =>
                                setAreaSearch(event.target.value)
                            }
                            placeholder="Search areas or branches…"
                            className="pl-9"
                            autoComplete="off"
                        />
                    </div>

                    {filteredAreas.length === 0 ? (
                        <p className="text-muted-foreground rounded-lg border border-dashed py-8 text-center text-sm">
                            {areaSearch.trim() === ''
                                ? 'No areas available.'
                                : `No areas match “${areaSearch.trim()}”.`}
                        </p>
                    ) : (
                        <div className="flex max-h-64 flex-col gap-2 overflow-y-auto pr-1">
                            {filteredAreas.map((area) => {
                                const selectedIds =
                                    'area_ids' in data &&
                                    Array.isArray(data.area_ids)
                                        ? data.area_ids
                                        : [];
                                const checked = selectedIds.includes(area.id);
                                const branchLabel =
                                    area.branch_names.length > 0
                                        ? area.branch_names.join(', ')
                                        : 'Unassigned';

                                return (
                                    <label
                                        key={area.id}
                                        htmlFor={`area-${area.id}`}
                                        className="border-border/70 hover:bg-muted/40 flex cursor-pointer items-start gap-3 rounded-lg border px-3 py-2.5"
                                    >
                                        <Checkbox
                                            id={`area-${area.id}`}
                                            checked={checked}
                                            onCheckedChange={(value) => {
                                                const next =
                                                    value === true
                                                        ? [
                                                              ...selectedIds,
                                                              area.id,
                                                          ]
                                                        : selectedIds.filter(
                                                              (id) =>
                                                                  id !==
                                                                  area.id,
                                                          );
                                                setData('area_ids', next);
                                            }}
                                            className="mt-0.5"
                                        />
                                        <span className="flex min-w-0 flex-col gap-0.5">
                                            <span className="text-sm font-medium">
                                                {area.name}
                                            </span>
                                            <span className="text-muted-foreground text-xs">
                                                Branches: {branchLabel}
                                            </span>
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                    )}
                    <InputError
                        message={fieldError(formErrors, 'area_ids')}
                    />
                </section>
            ) : null}

            {includeSuperAdmin && 'super_admin' in data ? (
                <section
                    className={cn(
                        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Branch admin
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Create the first person who can sign in and manage
                            this branch.
                        </p>
                    </div>

                    <div className="flex flex-col gap-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="super_admin_name">
                                    Full name
                                </Label>
                                <Input
                                    id="super_admin_name"
                                    value={superAdmin.name}
                                    onChange={(event) =>
                                        setSuperAdmin({
                                            name: event.target.value,
                                        })
                                    }
                                    required
                                    placeholder="Alex Morgan"
                                    autoComplete="off"
                                />
                                <InputError
                                    message={fieldError(
                                        formErrors,
                                        'super_admin.name',
                                    )}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="super_admin_phone">
                                    Mobile number
                                </Label>
                                <Input
                                    id="super_admin_phone"
                                    type="tel"
                                    value={superAdmin.phone}
                                    onChange={(event) =>
                                        setSuperAdmin({
                                            phone: event.target.value,
                                        })
                                    }
                                    onBlur={() => validate('super_admin.phone')}
                                    required
                                    placeholder="0412 345 678"
                                />
                                <InputError
                                    message={fieldError(
                                        formErrors,
                                        'super_admin.phone',
                                    )}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="super_admin_email">
                                Email{' '}
                                <span className="text-muted-foreground font-normal">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="super_admin_email"
                                type="email"
                                value={superAdmin.email}
                                onChange={(event) =>
                                    setSuperAdmin({ email: event.target.value })
                                }
                                onBlur={() => {
                                    if (superAdmin.email.trim() !== '') {
                                        validate('super_admin.email');
                                    }
                                }}
                                placeholder="alex@puffmemate.com"
                            />
                            <InputError
                                message={fieldError(
                                    formErrors,
                                    'super_admin.email',
                                )}
                            />
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="super_admin_password">
                                    Password
                                </Label>
                                <PasswordInput
                                    id="super_admin_password"
                                    value={superAdmin.password}
                                    onChange={(event) =>
                                        setSuperAdmin({
                                            password: event.target.value,
                                        })
                                    }
                                    required
                                    autoComplete="new-password"
                                    placeholder="Choose a password"
                                />
                                <InputError
                                    message={fieldError(
                                        formErrors,
                                        'super_admin.password',
                                    )}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="super_admin_password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="super_admin_password_confirmation"
                                    value={superAdmin.password_confirmation}
                                    onChange={(event) =>
                                        setSuperAdmin({
                                            password_confirmation:
                                                event.target.value,
                                        })
                                    }
                                    required
                                    autoComplete="new-password"
                                    placeholder="Type password again"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            ) : null}

            <div className="bg-background/95 supports-[backdrop-filter]:bg-background/80 sticky bottom-0 z-10 -mx-2 flex flex-wrap items-center justify-end gap-3 border-t px-2 py-4 backdrop-blur">
                <Button variant="secondary" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ??
                        (isEdit
                            ? 'Save changes'
                            : includeSuperAdmin
                              ? 'Create branch'
                              : 'Save')}
                </Button>
            </div>
        </form>
    );
}
