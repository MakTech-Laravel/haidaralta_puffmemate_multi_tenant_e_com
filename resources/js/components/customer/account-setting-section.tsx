import { Camera } from 'lucide-react';
import { Form, usePage } from '@inertiajs/react';
import { useEffect, useState, type ChangeEvent } from 'react';
import AccountController from '@/actions/App/Http/Controllers/Customer/AccountController';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import {
    AccountRegionSelect,
    type AccountRegionOption,
} from '@/components/customer/account-region-select';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

const DEFAULT_AVATAR = '/images/customer/profile.png';

export type CustomerShippingAddress = {
    region: string | null;
    city: string | null;
    zip: string | null;
    address: string | null;
};

type AccountSettingSectionProps = {
    shippingAddress?: CustomerShippingAddress | null;
    regions?: readonly AccountRegionOption[];
    className?: string;
};

function initialsFromName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/u).filter(Boolean);

    if (parts.length === 0) {
        return '';
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
}

export function AccountSettingSection({
    shippingAddress = null,
    regions,
    className,
}: AccountSettingSectionProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const [fullName, setFullName] = useState(user?.name ?? '');
    const [region, setRegion] = useState(shippingAddress?.region ?? '');
    const [previewUrl, setPreviewUrl] = useState(
        user?.avatar || DEFAULT_AVATAR,
    );

    useEffect(() => {
        return () => {
            if (previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    if (!user) {
        return null;
    }

    const initials =
        initialsFromName(fullName) || user.name.charAt(0).toUpperCase();

    const onAvatarChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const nextUrl = URL.createObjectURL(file);
        setPreviewUrl((current) => {
            if (current.startsWith('blob:')) {
                URL.revokeObjectURL(current);
            }

            return nextUrl;
        });
    };

    return (
        <section
            className={cn(
                'bg-muted w-full min-w-0 space-y-6 p-5 sm:p-6',
                className,
            )}
        >
            <h2 className={accountSectionTitleClass}>Account Setting</h2>

            <Form
                {...AccountController.updateProfile.form()}
                encType="multipart/form-data"
                options={{ preserveScroll: true }}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="relative w-fit">
                            <Avatar className="size-24">
                                <AvatarImage src={previewUrl} alt="" />
                                <AvatarFallback className="text-lg font-semibold">
                                    {initials}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                className="bg-primary text-primary-foreground pointer-events-none absolute right-0 bottom-0 flex size-8 items-center justify-center rounded-full"
                                aria-hidden
                            >
                                <Camera className="size-4" />
                            </span>
                            <input
                                type="file"
                                name="avatar"
                                accept="image/*"
                                className="absolute inset-0 cursor-pointer opacity-0"
                                aria-label="Change profile photo"
                                onChange={onAvatarChange}
                            />
                        </div>
                        <InputError message={errors.avatar} />

                        <div className="grid gap-2">
                            <Label
                                htmlFor="account-full-name"
                                className={accountLabelClass}
                            >
                                Full Name
                            </Label>
                            <Input
                                id="account-full-name"
                                name="name"
                                value={fullName}
                                onChange={(event) =>
                                    setFullName(event.target.value)
                                }
                                autoComplete="name"
                                placeholder="Full Name"
                                required
                                className={accountInputClass}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-email"
                                    className={accountLabelClass}
                                >
                                    Email
                                </Label>
                                <Input
                                    id="account-email"
                                    type="email"
                                    name="email"
                                    defaultValue={user.email ?? ''}
                                    autoComplete="email"
                                    placeholder="Email"
                                    className={accountInputClass}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-phone"
                                    className={accountLabelClass}
                                >
                                    Phone Number
                                </Label>
                                <Input
                                    id="account-phone"
                                    type="tel"
                                    name="phone"
                                    defaultValue={user.phone}
                                    autoComplete="tel"
                                    placeholder="Phone Number"
                                    required
                                    className={accountInputClass}
                                />
                                <InputError message={errors.phone} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <AccountRegionSelect
                                id="account-region"
                                name="region"
                                value={region}
                                onValueChange={setRegion}
                                error={errors.region}
                                regions={regions}
                            />
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-city"
                                    className={accountLabelClass}
                                >
                                    City
                                </Label>
                                <Input
                                    id="account-city"
                                    name="city"
                                    placeholder="City"
                                    defaultValue={shippingAddress?.city ?? ''}
                                    className={accountInputClass}
                                />
                                <InputError message={errors.city} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-zip"
                                    className={accountLabelClass}
                                >
                                    Zip Code
                                </Label>
                                <Input
                                    id="account-zip"
                                    name="zip"
                                    placeholder="Zip Code"
                                    defaultValue={shippingAddress?.zip ?? ''}
                                    className={accountInputClass}
                                />
                                <InputError message={errors.zip} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="account-address"
                                className={accountLabelClass}
                            >
                                Address
                            </Label>
                            <Input
                                id="account-address"
                                name="address"
                                placeholder="Address"
                                defaultValue={shippingAddress?.address ?? ''}
                                className={accountInputClass}
                            />
                            <InputError message={errors.address} />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className={accountButtonClass}
                        >
                            Save Changes
                        </Button>
                    </>
                )}
            </Form>
        </section>
    );
}
