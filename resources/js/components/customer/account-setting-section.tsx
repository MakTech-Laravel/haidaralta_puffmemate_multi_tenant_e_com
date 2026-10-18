import { Camera } from "lucide-react";
import { Form, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState, type ChangeEvent } from "react";
import AccountController from "@/actions/App/Http/Controllers/Customer/AccountController";
import {
    accountButtonClass,
    accountInputClass,
    accountLabelClass,
    accountSectionTitleClass,
} from "@/components/customer/account-field-styles";
import InputError from "@/components/input-error";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import type { Auth } from "@/types";

type AccountSettingSectionProps = {
    className?: string;
};

function initialsFromName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/u).filter(Boolean);

    if (parts.length === 0) {
        return "";
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
}

export function AccountSettingSection({ className }: AccountSettingSectionProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const avatarInput = useRef<HTMLInputElement>(null);
    const [fullName, setFullName] = useState(user?.name ?? "");
    const [previewUrl, setPreviewUrl] = useState<string | null>(
        user?.avatar || null,
    );
    const [removeAvatar, setRemoveAvatar] = useState(false);

    useEffect(() => {
        setPreviewUrl(user?.avatar || null);
        setRemoveAvatar(false);
    }, [user?.avatar]);

    useEffect(() => {
        return () => {
            if (previewUrl?.startsWith("blob:")) {
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

        if (!file || !file.type.startsWith("image/")) {
            return;
        }

        setRemoveAvatar(false);
        setPreviewUrl(URL.createObjectURL(file));
    };

    const onRemoveAvatar = () => {
        if (avatarInput.current) {
            avatarInput.current.value = "";
        }

        setRemoveAvatar(true);
        setPreviewUrl(null);
    };

    return (
        <section
            className={cn(
                "bg-muted w-full min-w-0 space-y-6 p-5 sm:p-6",
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
                        <div className="flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-10">
                            <div className="flex shrink-0 flex-col items-start gap-2">
                                <div className="relative w-fit">
                                    <Avatar className="size-24">
                                        {previewUrl ? (
                                            <AvatarImage
                                                src={previewUrl}
                                                alt=""
                                            />
                                        ) : null}
                                        <AvatarFallback className="bg-primary/10 text-primary text-2xl font-semibold">
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
                                        ref={avatarInput}
                                        type="file"
                                        name="avatar"
                                        accept="image/*"
                                        className="absolute inset-0 cursor-pointer opacity-0"
                                        aria-label="Change profile photo"
                                        onChange={onAvatarChange}
                                    />
                                </div>
                                {removeAvatar ? (
                                    <input
                                        type="hidden"
                                        name="remove_avatar"
                                        value="1"
                                    />
                                ) : null}
                                {previewUrl ? (
                                    <button
                                        type="button"
                                        onClick={onRemoveAvatar}
                                        className="text-muted-foreground hover:text-foreground text-xs underline underline-offset-2"
                                    >
                                        Remove photo
                                    </button>
                                ) : null}
                                <InputError message={errors.avatar} />
                            </div>

                            <div className="min-w-0 flex-1 space-y-4">
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
                                            defaultValue={user.email ?? ""}
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

                                <div className="mt-4">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className={accountButtonClass}
                                    >
                                        Save Changes
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </section>
    );
}
