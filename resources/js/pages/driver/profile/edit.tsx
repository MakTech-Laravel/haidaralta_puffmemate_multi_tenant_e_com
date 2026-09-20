import { Head, Link, useForm } from '@inertiajs/react';
import { Bike, Camera, IdCard, Mail, MapPin, Phone } from 'lucide-react';
import {
    useEffect,
    useRef,
    useState,
    type ChangeEvent,
    type FormEvent,
    type ReactNode,
} from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Driver/ProfileController';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
    avatar_url: string | null;
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

function pickImage(file: File | undefined): string | File {
    if (!file) {
        return 'Choose an image file.';
    }

    const allowedType =
        file.type === 'image/jpeg' ||
        file.type === 'image/png' ||
        file.type === 'image/webp' ||
        /\.(jpe?g|png|webp)$/iu.test(file.name);

    if (!allowedType) {
        return 'Use a PNG, JPG, or WebP image.';
    }

    if (file.size > MAX_IMAGE_BYTES) {
        return 'The image must be 2MB or smaller.';
    }

    return file;
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
    const avatarObjectUrl = useRef<string | null>(null);
    const vehicleObjectUrl = useRef<string | null>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(
        driver.avatar_url,
    );
    const [vehiclePreview, setVehiclePreview] = useState<string | null>(
        driver.vehicle_image_url,
    );
    const [avatarReject, setAvatarReject] = useState<string | undefined>();
    const [vehicleReject, setVehicleReject] = useState<string | undefined>();

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

        return () => {
            if (avatarObjectUrl.current) {
                URL.revokeObjectURL(avatarObjectUrl.current);
            }

            if (vehicleObjectUrl.current) {
                URL.revokeObjectURL(vehicleObjectUrl.current);
            }
        };
    }, [setValidationTimeout]);

    const replacePreview = (
        store: typeof avatarObjectUrl,
        setPreview: (url: string | null) => void,
        next: File | null,
        fallback: string | null,
    ): void => {
        if (store.current) {
            URL.revokeObjectURL(store.current);
            store.current = null;
        }

        if (next) {
            const url = URL.createObjectURL(next);
            store.current = url;
            setPreview(url);

            return;
        }

        setPreview(fallback);
    };

    const onAvatarChange = (event: ChangeEvent<HTMLInputElement>): void => {
        const picked = pickImage(event.target.files?.[0]);
        event.target.value = '';

        if (typeof picked === 'string') {
            setAvatarReject(picked);

            return;
        }

        setAvatarReject(undefined);
        replacePreview(avatarObjectUrl, setAvatarPreview, picked, null);
        setData((current) => ({
            ...current,
            avatar: picked,
            remove_avatar: false,
        }));
    };

    const removeAvatar = (): void => {
        setAvatarReject(undefined);
        replacePreview(avatarObjectUrl, setAvatarPreview, null, null);
        setData((current) => ({
            ...current,
            avatar: null,
            remove_avatar: true,
        }));
    };

    const onVehicleChange = (event: ChangeEvent<HTMLInputElement>): void => {
        const picked = pickImage(event.target.files?.[0]);
        event.target.value = '';

        if (typeof picked === 'string') {
            setVehicleReject(picked);

            return;
        }

        setVehicleReject(undefined);
        replacePreview(vehicleObjectUrl, setVehiclePreview, picked, null);
        setData((current) => ({
            ...current,
            vehicle_image: picked,
            remove_vehicle_image: false,
        }));
    };

    const removeVehicle = (): void => {
        setVehicleReject(undefined);
        replacePreview(vehicleObjectUrl, setVehiclePreview, null, null);
        setData((current) => ({
            ...current,
            vehicle_image: null,
            remove_vehicle_image: true,
        }));
    };

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
                        <div className="relative size-20 shrink-0">
                            <Avatar className="size-full">
                                {avatarPreview ? (
                                    <AvatarImage
                                        src={avatarPreview}
                                        alt={data.name}
                                    />
                                ) : null}
                                <AvatarFallback className="text-base sm:text-lg">
                                    {getInitials(data.name)}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                className="bg-primary text-primary-foreground pointer-events-none absolute right-0 bottom-0 flex size-7 items-center justify-center rounded-full ring-2 ring-background"
                                aria-hidden
                            >
                                <Camera className="size-3.5" />
                            </span>
                            <input
                                type="file"
                                accept={MEDIA_ACCEPT}
                                disabled={processing}
                                className="absolute inset-0 z-10 cursor-pointer opacity-0"
                                aria-label="Change profile photo"
                                onChange={onAvatarChange}
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
                            <InputError
                                message={errors.name ?? errors.avatar ?? avatarReject}
                            />
                        </div>
                    </div>
                    <div className="flex flex-col gap-2 sm:items-end">
   
                        {avatarPreview ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                disabled={processing}
                                onClick={removeAvatar}
                            >
                                Remove photo
                            </Button>
                        ) : null}
                        <Button
                            type="submit"
                            size="lg"
                            disabled={processing}
                            className="hidden h-12 w-full sm:inline-flex sm:w-auto"
                        >
                            {processing ? <Spinner /> : null}
                            Save
                        </Button>
                    </div>
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
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="text-foreground text-base font-semibold">
                            Vehicle
                        </h2>
                        {vehiclePreview ? (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                disabled={processing}
                                onClick={removeVehicle}
                            >
                                Remove photo
                            </Button>
                        ) : null}
                    </div>
                    <div className="relative">
                        {vehiclePreview ? (
                            <img
                                src={vehiclePreview}
                                alt="Your vehicle"
                                className="border-border h-40 w-full rounded-lg border object-cover sm:h-52"
                            />
                        ) : (
                            <div className="bg-muted text-muted-foreground flex h-28 items-center justify-center gap-2 rounded-lg text-sm">
                                <Bike className="size-4" aria-hidden />
                                Add a vehicle photo
                            </div>
                        )}
                        <span
                            className="bg-primary text-primary-foreground pointer-events-none absolute right-3 bottom-3 flex size-9 items-center justify-center rounded-full shadow-sm"
                            aria-hidden
                        >
                            <Camera className="size-4" />
                        </span>
                        <input
                            type="file"
                            accept={MEDIA_ACCEPT}
                            disabled={processing}
                            className="absolute inset-0 z-10 cursor-pointer opacity-0"
                            aria-label="Change vehicle photo"
                            onChange={onVehicleChange}
                        />
                    </div>
                    <InputError
                        message={errors.vehicle_image ?? vehicleReject}
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
