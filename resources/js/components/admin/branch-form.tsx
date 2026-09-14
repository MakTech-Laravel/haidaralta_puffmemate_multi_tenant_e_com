import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
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
    opening_time?: string | null;
    closing_time?: string | null;
    status?: boolean;
}

interface BranchFormProps {
    action: FormAction;
    defaults?: BranchFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
    /** When true (create only), require first branch super-admin in the same submit. */
    includeSuperAdmin?: boolean;
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
}: BranchFormProps) {
    const [slugTouched, setSlugTouched] = useState(isEdit);
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

    const formMethod = (
        action.method ?? 'post'
    ).toLowerCase() as 'get' | 'post' | 'put' | 'patch' | 'delete';

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
        opening_time: defaults?.opening_time ?? '',
        closing_time: defaults?.closing_time ?? '',
        status: defaults?.status ?? true,
        ...(includeSuperAdmin
            ? {
                  super_admin: {
                      name: '',
                      phone: '',
                      email: '',
                      password: '',
                      password_confirmation: '',
                  },
              }
            : {}),
        ...(isEdit ? { _method: 'put' } : {}),
    });

    const formErrors = errors as FormErrors;

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
            const { logo, remove_logo, ...rest } = form;

            return {
                ...rest,
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
                    'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                    'sm:p-6',
                )}
            >
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        {isEdit ? 'Branch details' : 'Branch information'}
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Location identity, contact, and trading hours.
                    </p>
                </div>

                <div className="flex flex-col gap-4">
                    <div className="flex items-stretch gap-4">
                        <div className="flex w-40 shrink-0 flex-col gap-1.5 self-stretch sm:w-44">
                            <Label>Logo</Label>
                            <div className="flex min-h-44 flex-1 flex-col">
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
                                    isUploading={processing}
                                    uploadProgress={
                                        progress?.percentage ?? null
                                    }
                                    onCancel={
                                        processing ? cancel : undefined
                                    }
                                    placeholder="Branch logo"
                                    hint="PNG, JPG, or WebP · Max 2MB"
                                    error={errors.logo}
                                    classNames={{
                                        wrapper:
                                            'flex h-full min-h-44 w-full flex-1 flex-col',
                                        dropzone:
                                            'flex h-full min-h-44 w-full flex-1 border-dashed border-border/60 bg-muted/30 px-3 py-4',
                                        dropzoneContent: 'gap-1.5',
                                        card: 'flex h-full w-full flex-col overflow-hidden border-0 bg-transparent shadow-none hover:shadow-none',
                                        cardMedia:
                                            'aspect-auto min-h-44 flex-1 bg-transparent',
                                        cardImage:
                                            'size-full object-cover object-center',
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
                                    placeholder="e.g. PuffOnMate Sydney CBD"
                                    autoComplete="off"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="slug">Slug</Label>
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
                                        placeholder="e.g. sydney-cbd"
                                    />
                                    <InputError message={errors.slug} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="code">Code</Label>
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
                                        placeholder="e.g. DTN"
                                    />
                                    <InputError message={errors.code} />
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(event) =>
                                            setData(
                                                'phone',
                                                event.target.value,
                                            )
                                        }
                                        onBlur={() => {
                                            if (data.phone.trim() !== '') {
                                                validate('phone');
                                            }
                                        }}
                                        placeholder="04XX XXX XXX or +61…"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData(
                                                'email',
                                                event.target.value,
                                            )
                                        }
                                        onBlur={() => {
                                            if (data.email.trim() !== '') {
                                                validate('email');
                                            }
                                        }}
                                        placeholder="branch@example.com"
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
                            placeholder="Street, city, area"
                            rows={3}
                        />
                        <InputError message={errors.address} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                value={data.latitude}
                                onChange={(event) =>
                                    setData('latitude', event.target.value)
                                }
                                placeholder="e.g. -33.8688"
                            />
                            <InputError message={errors.latitude} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                value={data.longitude}
                                onChange={(event) =>
                                    setData('longitude', event.target.value)
                                }
                                placeholder="e.g. 151.2093"
                            />
                            <InputError message={errors.longitude} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="opening_time">Opening time</Label>
                            <TimeSelect
                                id="opening_time"
                                value={data.opening_time}
                                onChange={(value) =>
                                    setData('opening_time', value)
                                }
                                placeholder="Select opening time"
                                aria-invalid={Boolean(errors.opening_time)}
                            />
                            <InputError message={errors.opening_time} />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="closing_time">Closing time</Label>
                            <TimeSelect
                                id="closing_time"
                                value={data.closing_time}
                                onChange={(value) =>
                                    setData('closing_time', value)
                                }
                                placeholder="Select closing time"
                                aria-invalid={Boolean(errors.closing_time)}
                            />
                            <InputError message={errors.closing_time} />
                        </div>
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                        <Checkbox
                            id="status"
                            checked={data.status}
                            onCheckedChange={(value) =>
                                setData('status', value === true)
                            }
                        />
                        <Label htmlFor="status" className="font-normal">
                            Active
                        </Label>
                        <InputError message={errors.status} />
                    </div>
                </div>
            </section>

            {includeSuperAdmin && 'super_admin' in data ? (
                <section
                    className={cn(
                        'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            First super-admin
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            This person can sign into the branch portal as
                            super-admin. Required to finish creating the branch.
                        </p>
                    </div>

                    <div className="flex flex-col gap-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="super_admin_name">Name</Label>
                                <Input
                                    id="super_admin_name"
                                    value={data.super_admin.name}
                                    onChange={(event) =>
                                        setData('super_admin', {
                                            ...data.super_admin,
                                            name: event.target.value,
                                        })
                                    }
                                    required
                                    placeholder="Full name"
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
                                    Phone number
                                </Label>
                                <Input
                                    id="super_admin_phone"
                                    type="tel"
                                    value={data.super_admin.phone}
                                    onChange={(event) =>
                                        setData('super_admin', {
                                            ...data.super_admin,
                                            phone: event.target.value,
                                        })
                                    }
                                    onBlur={() =>
                                        validate('super_admin.phone')
                                    }
                                    required
                                    placeholder="04XX XXX XXX"
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
                                Email (optional)
                            </Label>
                            <Input
                                id="super_admin_email"
                                type="email"
                                value={data.super_admin.email}
                                onChange={(event) =>
                                    setData('super_admin', {
                                        ...data.super_admin,
                                        email: event.target.value,
                                    })
                                }
                                onBlur={() => {
                                    if (data.super_admin.email.trim() !== '') {
                                        validate('super_admin.email');
                                    }
                                }}
                                placeholder="admin@example.com"
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
                                    value={data.super_admin.password}
                                    onChange={(event) =>
                                        setData('super_admin', {
                                            ...data.super_admin,
                                            password: event.target.value,
                                        })
                                    }
                                    required
                                    autoComplete="new-password"
                                    placeholder="Enter password"
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
                                    value={
                                        data.super_admin.password_confirmation
                                    }
                                    onChange={(event) =>
                                        setData('super_admin', {
                                            ...data.super_admin,
                                            password_confirmation:
                                                event.target.value,
                                        })
                                    }
                                    required
                                    autoComplete="new-password"
                                    placeholder="Confirm password"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            ) : null}

            <div className="sticky bottom-0 z-10 -mx-2 flex flex-wrap items-center justify-end gap-3 border-t bg-background/95 px-2 py-4 backdrop-blur supports-[backdrop-filter]:bg-background/80">
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
