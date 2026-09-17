import { Camera } from 'lucide-react';
import { Form, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState, type ChangeEvent } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from '@/components/customer/account-field-styles';
import { AccountRegionSelect } from '@/components/customer/account-region-select';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

const DEFAULT_AVATAR = '/images/customer/profile.png';

type AccountSettingSectionProps = {
    className?: string;
};

function splitName(fullName: string): { firstName: string; lastName: string } {
    const parts = fullName.trim().split(/\s+/u).filter(Boolean);

    if (parts.length === 0) {
        return { firstName: '', lastName: '' };
    }

    if (parts.length === 1) {
        return { firstName: parts[0], lastName: '' };
    }

    return {
        firstName: parts[0],
        lastName: parts.slice(1).join(' '),
    };
}

export function AccountSettingSection({
    className,
}: AccountSettingSectionProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const initial = useMemo(() => splitName(user?.name ?? ''), [user?.name]);
    const [firstName, setFirstName] = useState(initial.firstName);
    const [lastName, setLastName] = useState(initial.lastName);
    const [region, setRegion] = useState('');
    const [previewUrl, setPreviewUrl] = useState(DEFAULT_AVATAR);

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
        `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() ||
        user.name.charAt(0).toUpperCase();

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
                    accept="image/*"
                    className="absolute inset-0 cursor-pointer opacity-0"
                    aria-label="Change profile photo"
                    onChange={onAvatarChange}
                />
            </div>

            <Form
                {...ProfileController.update.form()}
                options={{ preserveScroll: true }}
                transform={(data) => ({
                    ...data,
                    name: [firstName, lastName]
                        .map((part) => part.trim())
                        .filter(Boolean)
                        .join(' '),
                })}
                className="space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-first-name"
                                    className={accountLabelClass}
                                >
                                    First name
                                </Label>
                                <Input
                                    id="account-first-name"
                                    value={firstName}
                                    onChange={(event) =>
                                        setFirstName(event.target.value)
                                    }
                                    autoComplete="given-name"
                                    placeholder="First name"
                                    required
                                    className={accountInputClass}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="account-last-name"
                                    className={accountLabelClass}
                                >
                                    Last name
                                </Label>
                                <Input
                                    id="account-last-name"
                                    value={lastName}
                                    onChange={(event) =>
                                        setLastName(event.target.value)
                                    }
                                    autoComplete="family-name"
                                    placeholder="Last name"
                                    className={accountInputClass}
                                />
                            </div>
                        </div>

                        <InputError message={errors.name} />

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
                                value={region}
                                onValueChange={setRegion}
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
                                    placeholder="City"
                                    defaultValue=""
                                    className={accountInputClass}
                                />
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
                                    placeholder="Zip Code"
                                    defaultValue=""
                                    className={accountInputClass}
                                />
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
                                placeholder="Address"
                                defaultValue=""
                                className={accountInputClass}
                            />
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
