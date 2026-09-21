import { Head, Link, useForm } from '@inertiajs/react';
import { Bike, IdCard, Mail, MapPin, Phone } from 'lucide-react';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
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
import { useInitials } from '@/hooks/use-initials';
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

function RequiredMark() {
    return (
        <span className="text-primary" aria-hidden>
            *
        </span>
    );
}

function EditField({
    icon: Icon,
    label,
    htmlFor,
    error,
    required = false,
    children,
}: {
    icon: typeof Phone;
    label: string;
    htmlFor: string;
    error?: string;
    required?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <span className="bg-muted mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Icon className="text-muted-foreground size-4" aria-hidden />
            </span>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <Label
                    htmlFor={htmlFor}
                    className="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                >
                    {label} {required ? <RequiredMark /> : null}
                </Label>
                {children}
                <InputError message={error} />
            </div>
        </div>
    );
}

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
    const getInitials = useInitials();
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
        transform,
        validate,
        setValidationTimeout,
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
                className="flex w-full flex-col gap-5"
                autoComplete="off"
            >
                <section className="bg-accent flex flex-col gap-4 rounded-xl p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="w-20 shrink-0">
                            <FileUpload
                                variant="avatar"
                                avatarSize={80}
                                avatarFallback={
                                    <span className="text-base font-semibold sm:text-lg">
                                        {getInitials(data.name)}
                                    </span>
                                }
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
                                maxSize={Number.POSITIVE_INFINITY}
                                maxFiles={1}
                                multiple={false}
                                disabled={processing}
                                showCaption={false}
                                error={errors.avatar}
                            />
                        </div>
                        <div className="min-w-0 flex-1 space-y-1">
                            <Label
                                htmlFor="name"
                                className="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                            >
                                Name <RequiredMark />
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autoComplete="name"
                                placeholder="Your name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                className="text-foreground h-auto min-h-0 border-0 bg-transparent px-0 py-0 text-xl font-bold tracking-tight shadow-none focus-visible:border-transparent focus-visible:ring-0 sm:text-2xl"
                            />
                            <p className="text-muted-foreground text-sm">
                                Change your photo
                            </p>
                            <InputError message={errors.name} />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing}
                        className="hidden h-12 w-full sm:inline-flex sm:w-auto"
                    >
                        {processing ? <Spinner /> : null}
                        Save
                    </Button>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Contact
                    </h2>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <EditField
                            icon={Phone}
                            label="Phone"
                            htmlFor="phone"
                            required
                            error={errors.phone}
                        >
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
                        </EditField>
                        <EditField
                            icon={Mail}
                            label="Email"
                            htmlFor="email"
                            error={errors.email}
                        >
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
                                    if (event.target.value.trim() !== '') {
                                        validate('email');
                                    }
                                }}
                            />
                        </EditField>
                        <EditField
                            icon={IdCard}
                            label="License"
                            htmlFor="license_number"
                            error={errors.license_number}
                        >
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
                        </EditField>
                        <EditField
                            icon={MapPin}
                            label="Address"
                            htmlFor="address"
                            error={errors.address}
                        >
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
                        </EditField>
                    </div>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Vehicle
                    </h2>
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
                            data.vehicle_image || data.remove_vehicle_image
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
                        maxSize={Number.POSITIVE_INFINITY}
                        maxFiles={1}
                        multiple={false}
                        dropzoneVisibility="empty"
                        lightbox
                        disabled={processing}
                        placeholder="Add a vehicle photo"
                        hint="PNG, JPG, or WebP"
                        error={errors.vehicle_image}
                        classNames={{
                            dropzone:
                                'min-h-28 rounded-lg border-0 bg-muted px-3 py-6 hover:bg-muted/80 hover:border-0',
                            dropzoneContent: 'gap-1',
                            card: 'overflow-hidden shadow-none',
                            cardMedia: 'h-40 w-full aspect-auto sm:h-52',
                            cardInfo: 'hidden',
                        }}
                    />
                    <EditField
                        icon={Bike}
                        label="Vehicle type"
                        htmlFor="vehicle_type"
                        required
                        error={errors.vehicle_type}
                    >
                        <Select
                            value={data.vehicle_type}
                            onValueChange={(value) =>
                                setData('vehicle_type', value)
                            }
                        >
                            <SelectTrigger
                                id="vehicle_type"
                                className="w-full"
                                aria-required
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
                    </EditField>
                </section>

                <Button
                    type="submit"
                    size="lg"
                    disabled={processing}
                    className="h-12 w-full sm:hidden"
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
