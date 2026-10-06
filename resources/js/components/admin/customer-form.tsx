import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { ACCOUNT_REGION_OPTIONS } from '@/components/customer/account-region-options';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type ShippingDefaults = {
    address?: string | null;
    city?: string | null;
    region?: string | null;
    zip?: string | null;
};

type BillingDefaults = {
    name?: string | null;
    address?: string | null;
    city?: string | null;
    region?: string | null;
    zip?: string | null;
    email?: string | null;
    phone?: string | null;
};

type CustomerFormDefaults = {
    name?: string;
    phone?: string;
    email?: string | null;
    is_active?: boolean;
    avatar?: string | null;
    avatar_url?: string | null;
    shipping?: ShippingDefaults | null;
    billing?: BillingDefaults | null;
};

type CustomerFormProps = {
    action: FormAction;
    cancelHref: string;
    submitLabel?: string;
    defaults?: CustomerFormDefaults;
    isEdit?: boolean;
};

const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';
const REGION_NONE = '__none__';

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

function ActiveToggle({
    value,
    onChange,
}: {
    value: boolean;
    onChange: (next: boolean) => void;
}) {
    const options = [
        { label: 'Active', active: true },
        { label: 'Inactive', active: false },
    ];

    return (
        <div className="grid gap-2 sm:grid-cols-2">
            {options.map((option) => {
                const selected = value === option.active;

                return (
                    <button
                        key={option.label}
                        type="button"
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

function RegionSelect({
    id,
    value,
    onChange,
    error,
}: {
    id: string;
    value: string;
    onChange: (next: string) => void;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Region / State</Label>
            <Select
                value={value === '' ? REGION_NONE : value}
                onValueChange={(next) =>
                    onChange(next === REGION_NONE ? '' : next)
                }
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue placeholder="Select region" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={REGION_NONE}>No region</SelectItem>
                    {ACCOUNT_REGION_OPTIONS.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}

export function CustomerForm({
    action,
    cancelHref,
    submitLabel = 'Create customer',
    defaults,
    isEdit = false,
}: CustomerFormProps) {
    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        defaults?.avatar && defaults?.avatar_url
            ? [
                  {
                      id: 'avatar',
                      path: defaults.avatar,
                      url: defaults.avatar_url,
                      mime_type: 'image/*',
                      size: 0,
                      name: defaults.avatar.split('/').pop() ?? 'avatar',
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
        clearErrors,
        cancel,
    } = useForm(formMethod, action.action, {
        name: defaults?.name ?? '',
        phone: defaults?.phone ?? '',
        email: defaults?.email ?? '',
        password: '',
        password_confirmation: '',
        is_active: defaults?.is_active ?? true,
        avatar: null as File | null,
        remove_avatar: false,
        shipping: {
            address: defaults?.shipping?.address ?? '',
            city: defaults?.shipping?.city ?? '',
            region: defaults?.shipping?.region ?? '',
            zip: defaults?.shipping?.zip ?? '',
        },
        billing: {
            name: defaults?.billing?.name ?? '',
            address: defaults?.billing?.address ?? '',
            city: defaults?.billing?.city ?? '',
            region: defaults?.billing?.region ?? '',
            zip: defaults?.billing?.zip ?? '',
            email: defaults?.billing?.email ?? '',
            phone: defaults?.billing?.phone ?? '',
        },
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const submit = (event: FormEvent): void => {
        event.preventDefault();

        transform((form) => {
            const {
                avatar,
                remove_avatar,
                password,
                password_confirmation,
                ...rest
            } = form;

            return {
                ...rest,
                ...(password ? { password, password_confirmation } : {}),
                ...(avatar ? { avatar } : {}),
                ...(remove_avatar ? { remove_avatar: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="w-full space-y-6" autoComplete="off">
            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Account
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Avatar, contact details, and login password for this
                        customer.
                    </p>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-4">
                    <div className="flex w-40 shrink-0 flex-col gap-1.5 sm:w-44">
                        <Label>Avatar</Label>
                        <FileUpload
                            variant="avatar"
                            avatarSize={176}
                            value={data.avatar}
                            onChange={(file) => {
                                const next = Array.isArray(file)
                                    ? (file[0] ?? null)
                                    : file;
                                setExistingAvatar([]);
                                setData((current) => ({
                                    ...current,
                                    avatar: next,
                                    remove_avatar: next
                                        ? false
                                        : current.remove_avatar,
                                }));
                            }}
                            existingFiles={
                                data.avatar || data.remove_avatar
                                    ? []
                                    : existingAvatar
                            }
                            onRemoveExisting={() => {
                                setExistingAvatar([]);
                                setData((current) => ({
                                    ...current,
                                    avatar: null,
                                    remove_avatar: true,
                                }));
                            }}
                            accept={MEDIA_ACCEPT}
                            maxSize={2}
                            maxFiles={1}
                            multiple={false}
                            isUploading={processing}
                            uploadProgress={progress?.percentage ?? null}
                            onCancel={processing ? cancel : undefined}
                            showCaption={false}
                            error={errors.avatar}
                        />
                    </div>

                    <div className="flex min-w-0 flex-1 flex-col gap-3">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name">
                                Name <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                onBlur={() => validate('name')}
                                autoComplete="name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="phone">
                                    Phone <RequiredMark />
                                </Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    onBlur={() => validate('phone')}
                                    autoComplete="tel"
                                    placeholder="+61…"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => {
                                        const next = event.target.value;
                                        setData('email', next);
                                        if (next.trim() === '') {
                                            clearErrors('email');
                                        }
                                    }}
                                    onBlur={() => {
                                        if (data.email.trim() === '') {
                                            clearErrors('email');

                                            return;
                                        }

                                        validate('email');
                                    }}
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password">
                                    {isEdit ? (
                                        'New password (optional)'
                                    ) : (
                                        <>
                                            Password <RequiredMark />
                                        </>
                                    )}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    onBlur={() => {
                                        if (
                                            isEdit &&
                                            data.password.trim() === ''
                                        ) {
                                            clearErrors('password');

                                            return;
                                        }

                                        validate('password');
                                    }}
                                    autoComplete="new-password"
                                    required={!isEdit}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password_confirmation">
                                    {isEdit ? (
                                        'Confirm password'
                                    ) : (
                                        <>
                                            Confirm password <RequiredMark />
                                        </>
                                    )}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    autoComplete="new-password"
                                    required={!isEdit}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Status
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Inactive customers cannot sign in.
                    </p>
                </div>
                <ActiveToggle
                    value={data.is_active}
                    onChange={(next) => setData('is_active', next)}
                />
                <InputError message={errors.is_active} />
            </section>

            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Shipping address
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Optional delivery address for this customer.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="shipping_address">Street address</Label>
                        <Textarea
                            id="shipping_address"
                            value={data.shipping.address}
                            onChange={(event) =>
                                setData('shipping', {
                                    ...data.shipping,
                                    address: event.target.value,
                                })
                            }
                            rows={3}
                        />
                        <InputError message={errors['shipping.address']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="shipping_city">City</Label>
                        <Input
                            id="shipping_city"
                            value={data.shipping.city}
                            onChange={(event) =>
                                setData('shipping', {
                                    ...data.shipping,
                                    city: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['shipping.city']} />
                    </div>
                    <RegionSelect
                        id="shipping_region"
                        value={data.shipping.region}
                        onChange={(next) =>
                            setData('shipping', {
                                ...data.shipping,
                                region: next,
                            })
                        }
                        error={errors['shipping.region']}
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="shipping_zip">Postcode</Label>
                        <Input
                            id="shipping_zip"
                            value={data.shipping.zip}
                            onChange={(event) =>
                                setData('shipping', {
                                    ...data.shipping,
                                    zip: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['shipping.zip']} />
                    </div>
                </div>
            </section>

            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Billing address
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Optional billing contact and address.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="billing_name">Billing name</Label>
                        <Input
                            id="billing_name"
                            value={data.billing.name}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    name: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['billing.name']} />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="billing_address">Street address</Label>
                        <Textarea
                            id="billing_address"
                            value={data.billing.address}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    address: event.target.value,
                                })
                            }
                            rows={3}
                        />
                        <InputError message={errors['billing.address']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="billing_city">City</Label>
                        <Input
                            id="billing_city"
                            value={data.billing.city}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    city: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['billing.city']} />
                    </div>
                    <RegionSelect
                        id="billing_region"
                        value={data.billing.region}
                        onChange={(next) =>
                            setData('billing', {
                                ...data.billing,
                                region: next,
                            })
                        }
                        error={errors['billing.region']}
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="billing_zip">Postcode</Label>
                        <Input
                            id="billing_zip"
                            value={data.billing.zip}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    zip: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['billing.zip']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="billing_email">Billing email</Label>
                        <Input
                            id="billing_email"
                            type="email"
                            value={data.billing.email}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    email: event.target.value,
                                })
                            }
                        />
                        <InputError message={errors['billing.email']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="billing_phone">Billing phone</Label>
                        <Input
                            id="billing_phone"
                            value={data.billing.phone}
                            onChange={(event) =>
                                setData('billing', {
                                    ...data.billing,
                                    phone: event.target.value,
                                })
                            }
                            placeholder="+61…"
                        />
                        <InputError message={errors['billing.phone']} />
                    </div>
                </div>
            </section>

            <div className="flex flex-wrap items-center justify-end gap-3">
                <Button variant="outline" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
