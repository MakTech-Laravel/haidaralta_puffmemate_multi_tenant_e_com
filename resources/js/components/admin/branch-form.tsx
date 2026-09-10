import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FormAction = {
    action: string;
    method?: string;
};

interface BranchFormDefaults {
    name?: string;
    slug?: string;
    icon?: string | null;
    icon_url?: string | null;
    address?: string | null;
    latitude?: string | number | null;
    longitude?: string | number | null;
    is_active?: boolean;
}

interface BranchFormProps {
    action: FormAction;
    defaults?: BranchFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function BranchForm({
    action,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
}: BranchFormProps) {
    const [slugTouched, setSlugTouched] = useState(isEdit);
    const [existingIconUrl, setExistingIconUrl] = useState<string | null>(
        defaults?.icon_url ?? null,
    );

    const { data, setData, post, processing, errors, transform } = useForm<{
        name: string;
        slug: string;
        icon: File | null;
        remove_icon: boolean;
        address: string;
        latitude: string;
        longitude: string;
        is_active: boolean;
        super_admin_name: string;
        super_admin_email: string;
        super_admin_password: string;
        super_admin_password_confirmation: string;
        _method?: string;
    }>({
        name: defaults?.name ?? '',
        slug: defaults?.slug ?? '',
        icon: null,
        remove_icon: false,
        address: defaults?.address ?? '',
        latitude:
            defaults?.latitude === null || defaults?.latitude === undefined
                ? ''
                : String(defaults.latitude),
        longitude:
            defaults?.longitude === null || defaults?.longitude === undefined
                ? ''
                : String(defaults.longitude),
        is_active: defaults?.is_active ?? true,
        super_admin_name: '',
        super_admin_email: '',
        super_admin_password: '',
        super_admin_password_confirmation: '',
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        if (!slugTouched) {
            setData('slug', slugify(data.name));
        }
    }, [data.name, slugTouched, setData]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const { icon, remove_icon, ...rest } = form;

            return {
                ...rest,
                ...(icon ? { icon } : {}),
                ...(remove_icon ? { remove_icon: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-2">
                <Label>Branch image</Label>
                <FileUploadAvatar
                    value={data.icon}
                    onChange={(file) => {
                        const next = Array.isArray(file)
                            ? (file[0] ?? null)
                            : file;
                        setData((current) => ({
                            ...current,
                            icon: next,
                            remove_icon: next ? false : current.remove_icon,
                        }));
                    }}
                    existingUrl={
                        data.icon || data.remove_icon ? null : existingIconUrl
                    }
                    onClearExisting={() => {
                        setExistingIconUrl(null);
                        setData((current) => ({
                            ...current,
                            icon: null,
                            remove_icon: true,
                        }));
                    }}
                    accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                    maxSize={2}
                    error={errors.icon}
                    hint="PNG, JPG, or WebP up to 2MB"
                    placeholder="Click to upload branch image"
                />
                <InputError message={errors.icon} />
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Branch name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(event) =>
                            setData('name', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="slug">Slug</Label>
                    <Input
                        id="slug"
                        value={data.slug}
                        onChange={(event) => {
                            setSlugTouched(true);
                            setData('slug', event.target.value);
                        }}
                        required
                    />
                    <InputError message={errors.slug} />
                </div>
            </div>

            <div className="">
                <div className="grid gap-2">
                    <Label htmlFor="address">Address</Label>
                    <Input
                        id="address"
                        value={data.address}
                        onChange={(event) =>
                            setData('address', event.target.value)
                        }
                    />
                    <InputError message={errors.address} />
                </div>
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="latitude">Latitude</Label>
                    <Input
                        id="latitude"
                        type="number"
                        step="any"
                        value={data.latitude}
                        onChange={(event) =>
                            setData('latitude', event.target.value)
                        }
                    />
                    <InputError message={errors.latitude} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="longitude">Longitude</Label>
                    <Input
                        id="longitude"
                        type="number"
                        step="any"
                        value={data.longitude}
                        onChange={(event) =>
                            setData('longitude', event.target.value)
                        }
                    />
                    <InputError message={errors.longitude} />
                </div>
            </div>

            {!isEdit && (
                <div className="space-y-4 rounded-xl border p-4">
                    <div>
                        <h2 className="text-sm font-medium">
                            Branch super-admin
                        </h2>
                        <p className="text-muted-foreground text-xs">
                            Required. This account can manage the new branch
                            portal.
                        </p>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="super_admin_name">Name</Label>
                            <Input
                                id="super_admin_name"
                                value={data.super_admin_name}
                                onChange={(event) =>
                                    setData(
                                        'super_admin_name',
                                        event.target.value,
                                    )
                                }
                                required
                            />
                            <InputError message={errors.super_admin_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="super_admin_email">Email</Label>
                            <Input
                                id="super_admin_email"
                                type="email"
                                value={data.super_admin_email}
                                onChange={(event) =>
                                    setData(
                                        'super_admin_email',
                                        event.target.value,
                                    )
                                }
                                required
                            />
                            <InputError message={errors.super_admin_email} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="super_admin_password">
                                Password
                            </Label>
                            <Input
                                id="super_admin_password"
                                type="password"
                                value={data.super_admin_password}
                                onChange={(event) =>
                                    setData(
                                        'super_admin_password',
                                        event.target.value,
                                    )
                                }
                                required
                                autoComplete="new-password"
                            />
                            <InputError message={errors.super_admin_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="super_admin_password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="super_admin_password_confirmation"
                                type="password"
                                value={data.super_admin_password_confirmation}
                                onChange={(event) =>
                                    setData(
                                        'super_admin_password_confirmation',
                                        event.target.value,
                                    )
                                }
                                required
                                autoComplete="new-password"
                            />
                        </div>
                    </div>
                </div>
            )}

            <div className="flex items-center gap-2">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(value) =>
                        setData('is_active', value === true)
                    }
                />
                <Label htmlFor="is_active" className="font-normal">
                    Active
                </Label>
                <InputError message={errors.is_active} />
            </div>

            <div className="flex items-center gap-3 border-t pt-5">
                <Button type="submit" disabled={processing}>
                    {submitLabel ?? (isEdit ? 'Save changes' : 'Create branch')}
                </Button>
                <Button variant="secondary" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
            </div>
        </form>
    );
}
