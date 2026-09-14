import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { TimeSelect } from '@/components/ui/time-select';

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
    const [existingLogoUrl, setExistingLogoUrl] = useState<string | null>(
        defaults?.logo_url ?? null,
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
        transform,
        validate,
        setValidationTimeout,
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
        ...(isEdit ? { _method: 'put' } : {}),
    });

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
        <form onSubmit={submit}>
            <Card>
                <CardHeader>
                    <CardTitle>
                        {isEdit ? 'Branch details' : 'Branch information'}
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-6">
                    <div className="flex flex-col gap-2">
                        <Label>Branch logo</Label>
                        <FileUploadAvatar
                            value={data.logo}
                            onChange={(file) => {
                                const next = Array.isArray(file)
                                    ? (file[0] ?? null)
                                    : file;
                                setData((current) => ({
                                    ...current,
                                    logo: next,
                                    remove_logo: next
                                        ? false
                                        : current.remove_logo,
                                }));
                            }}
                            existingUrl={
                                data.logo || data.remove_logo
                                    ? null
                                    : existingLogoUrl
                            }
                            onClearExisting={() => {
                                setExistingLogoUrl(null);
                                setData((current) => ({
                                    ...current,
                                    logo: null,
                                    remove_logo: true,
                                }));
                            }}
                            accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                            maxSize={2}
                            error={errors.logo}
                            hint="PNG, JPG, or WebP up to 2MB"
                            placeholder="Branch logo"
                        />
                        <InputError message={errors.logo} />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">Branch name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                                placeholder="e.g. PuffOnMate Sydney CBD"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(event) => {
                                    setSlugTouched(true);
                                    setData('slug', event.target.value);
                                }}
                                onBlur={() => validate('slug')}
                                required
                                placeholder="e.g. sydney-cbd"
                            />
                            <InputError message={errors.slug} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(event) =>
                                    setData('code', event.target.value)
                                }
                                onBlur={() => validate('code')}
                                required
                                placeholder="e.g. DTN"
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="flex flex-col gap-2">
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
                                placeholder="04XX XXX XXX or +61…"
                            />
                            <InputError message={errors.phone} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="email">Email</Label>
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
                                placeholder="branch@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="flex flex-col gap-2 sm:col-span-2">
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
                    </div>

                    <Separator />

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                type="number"
                                step="any"
                                value={data.latitude}
                                onChange={(event) =>
                                    setData('latitude', event.target.value)
                                }
                                placeholder="e.g. 23.8103"
                            />
                            <InputError message={errors.latitude} />
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                type="number"
                                step="any"
                                value={data.longitude}
                                onChange={(event) =>
                                    setData('longitude', event.target.value)
                                }
                                placeholder="e.g. 90.4125"
                            />
                            <InputError message={errors.longitude} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
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

                        <div className="flex flex-col gap-2">
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

                    <Separator />

                    <div className="flex items-center gap-2">
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
                </CardContent>
                <CardFooter className="flex items-center gap-3 border-t pt-6">
                    <Button type="submit" disabled={processing}>
                        {processing ? <Spinner /> : null}
                        {submitLabel ?? (isEdit ? 'Save changes' : 'Next')}
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link href={cancelHref}>Cancel</Link>
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
