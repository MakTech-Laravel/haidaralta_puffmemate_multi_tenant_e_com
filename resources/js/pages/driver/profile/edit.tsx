import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Driver/ProfileController';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
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
import { edit as editProfile, index as profileIndex } from '@/routes/driver/profile';
import { notice } from '@/routes/phone/verification';

const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

type Option = { value: string; label: string };

type DriverEditProfile = {
    name: string;
    phone: string;
    email: string | null;
    license_number: string | null;
    vehicle_type: string | null;
    address: string | null;
    avatar: string | null;
    avatar_url: string | null;
    vehicle_image_path: string | null;
    vehicle_image_url: string | null;
};

function existingImage(
    id: string,
    path: string | null,
    url: string | null,
): ExistingFile[] {
    if (!path || !url) {
        return [];
    }

    return [
        {
            id,
            path,
            url,
            mime_type: 'image/jpeg',
            name: path.split('/').pop() ?? id,
        },
    ];
}

export default function DriverProfileEdit({
    driver,
    vehicleTypes,
    mustVerifyPhone,
}: {
    driver: DriverEditProfile;
    vehicleTypes: Option[];
    mustVerifyPhone: boolean;
}) {
    const action = ProfileController.update.form();
    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        existingImage('avatar', driver.avatar, driver.avatar_url),
    );
    const [existingVehicle, setExistingVehicle] = useState<ExistingFile[]>(() =>
        existingImage(
            'vehicle',
            driver.vehicle_image_path,
            driver.vehicle_image_url,
        ),
    );

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
    } = useForm('post', action.action, {
        name: driver.name,
        phone: driver.phone,
        email: driver.email ?? '',
        license_number: driver.license_number ?? '',
        vehicle_type: driver.vehicle_type ?? vehicleTypes[0]?.value ?? '',
        address: driver.address ?? '',
        avatar: null as File | null,
        vehicle_image: null as File | null,
        remove_avatar: false,
        remove_vehicle_image: false,
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const submit = (event: FormEvent): void => {
        event.preventDefault();

        transform((form) => {
            const {
                avatar,
                vehicle_image,
                remove_avatar,
                remove_vehicle_image,
                ...rest
            } = form;

            return {
                ...rest,
                ...(avatar ? { avatar } : {}),
                ...(vehicle_image ? { vehicle_image } : {}),
                ...(remove_avatar ? { remove_avatar: true } : {}),
                ...(remove_vehicle_image ? { remove_vehicle_image: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Edit details" />

            <form
                onSubmit={submit}
                className="flex max-w-2xl flex-col gap-4"
                autoComplete="off"
            >
                <header className="flex items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-foreground text-xl font-bold tracking-tight">
                            Edit details
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Photo, contact, and vehicle
                        </p>
                    </div>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="hidden shrink-0 sm:inline-flex"
                    >
                        {processing ? <Spinner /> : null}
                        Save
                    </Button>
                </header>

                <section className="border-border space-y-3 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        You
                    </h2>
                    <div className="flex items-start gap-4">
                        <div className="flex w-[5.5rem] shrink-0 flex-col items-center gap-1.5">
                            <FileUpload
                                variant="avatar"
                                avatarSize={88}
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
                            <InputError message={errors.avatar} />
                        </div>

                        <div className="grid min-w-0 flex-1 gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Daniel Foster"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        required
                                        autoComplete="tel"
                                        placeholder="04XX XXX XXX"
                                        value={data.phone}
                                        onChange={(event) =>
                                            setData('phone', event.target.value)
                                        }
                                        onBlur={() => validate('phone')}
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="Optional"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData('email', event.target.value)
                                        }
                                        onBlur={(event) => {
                                            if (
                                                event.target.value.trim() !== ''
                                            ) {
                                                validate('email');
                                            }
                                        }}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                            {mustVerifyPhone ? (
                                <p className="text-muted-foreground text-sm">
                                    Phone unverified.{' '}
                                    <Link
                                        href={notice()}
                                        className="text-foreground font-medium underline underline-offset-4"
                                    >
                                        Verify
                                    </Link>
                                </p>
                            ) : null}
                        </div>
                    </div>
                </section>

                <section className="border-border space-y-3 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        License & vehicle
                    </h2>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="license_number">
                                License number
                            </Label>
                            <Input
                                id="license_number"
                                name="license_number"
                                placeholder="DL-12345"
                                value={data.license_number}
                                onChange={(event) =>
                                    setData(
                                        'license_number',
                                        event.target.value,
                                    )
                                }
                                onBlur={() => validate('license_number')}
                            />
                            <InputError message={errors.license_number} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="vehicle_type">Vehicle type</Label>
                            <Select
                                value={data.vehicle_type}
                                onValueChange={(value) =>
                                    setData('vehicle_type', value)
                                }
                            >
                                <SelectTrigger
                                    id="vehicle_type"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    {vehicleTypes.map((type) => (
                                        <SelectItem
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.vehicle_type} />
                        </div>
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label htmlFor="address">Address</Label>
                            <Input
                                id="address"
                                name="address"
                                autoComplete="street-address"
                                placeholder="Street, suburb, city"
                                value={data.address}
                                onChange={(event) =>
                                    setData('address', event.target.value)
                                }
                            />
                            <InputError message={errors.address} />
                        </div>
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Vehicle photo</Label>
                            <FileUpload
                                value={data.vehicle_image}
                                onChange={(file) => {
                                    const next = Array.isArray(file)
                                        ? (file[0] ?? null)
                                        : file;
                                    setExistingVehicle([]);
                                    setData((current) => ({
                                        ...current,
                                        vehicle_image: next,
                                        remove_vehicle_image: next
                                            ? false
                                            : current.remove_vehicle_image,
                                    }));
                                }}
                                existingFiles={
                                    data.vehicle_image ||
                                    data.remove_vehicle_image
                                        ? []
                                        : existingVehicle
                                }
                                onRemoveExisting={() => {
                                    setExistingVehicle([]);
                                    setData((current) => ({
                                        ...current,
                                        vehicle_image: null,
                                        remove_vehicle_image: true,
                                    }));
                                }}
                                accept={MEDIA_ACCEPT}
                                maxSize={2}
                                maxFiles={1}
                                multiple={false}
                                compactCards
                                dropzoneVisibility="empty"
                                lightbox
                                isUploading={processing}
                                uploadProgress={progress?.percentage ?? null}
                                onCancel={processing ? cancel : undefined}
                                placeholder="Add a photo of your vehicle"
                                hint="PNG, JPG, or WebP · up to 2MB"
                                error={errors.vehicle_image}
                                classNames={{
                                    dropzone:
                                        'min-h-28 border-dashed px-3 py-5',
                                    dropzoneContent: 'gap-1',
                                }}
                            />
                            <InputError message={errors.vehicle_image} />
                        </div>
                    </div>
                </section>

                <Button
                    type="submit"
                    disabled={processing}
                    className="w-full sm:hidden"
                >
                    {processing ? <Spinner /> : null}
                    Save
                </Button>
            </form>
        </>
    );
}

DriverProfileEdit.layout = {
    breadcrumbs: [
        {
            title: 'Profile',
            href: profileIndex.url(),
        },
        {
            title: 'Edit details',
            href: editProfile.url(),
        },
    ],
};
