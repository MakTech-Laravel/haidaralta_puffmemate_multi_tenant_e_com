import { Head, Link, setLayoutProps, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import type { Props as ManagePasskeysProps } from '@/components/manage-passkeys';
import ManagePasskeys from '@/components/manage-passkeys';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import ChangePasswordCard from '@/components/settings/change-password-card';
import BranchAccountPage from '@/layouts/settings/branch-account-page';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit } from '@/routes/profile';
import { notice } from '@/routes/phone/verification';
import type { Auth } from '@/types';

const AVATAR_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

type ProfilePayload = {
    name: string;
    phone: string;
    email: string | null;
    avatar: string | null;
    avatar_url: string | null;
};

type PageProps = {
    auth: Auth;
    portal: string | null;
    branch: { slug: string } | null;
    profile: ProfilePayload | null;
    mustVerifyPhone: boolean;
    canDeleteAccount: boolean;
    status?: string;
};

function profileBreadcrumbs(portal: string | null, branchSlug?: string) {
    if (portal === 'platform') {
        return [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Profile', href: edit() },
        ];
    }

    if (portal === 'branch' && branchSlug) {
        return [
            { title: 'Dashboard', href: `/branch/${branchSlug}/dashboard` },
            { title: 'Profile', href: edit() },
        ];
    }

    return [{ title: 'Profile', href: edit() }];
}

export default function Profile({
    mustVerifyPhone,
    status,
    profile,
    canDeleteAccount,
    passwordRules,
    canManageTwoFactor,
    requiresConfirmation,
    twoFactorEnabled,
    canManagePasskeys,
    passkeys,
}: {
    mustVerifyPhone: boolean;
    status?: string;
    profile: ProfilePayload | null;
    canDeleteAccount: boolean;
    passwordRules?: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps) {
    const { auth, portal, branch } = usePage<PageProps>().props;
    const user = auth.user;

    setLayoutProps({
        breadcrumbs: profileBreadcrumbs(portal, branch?.slug),
    });

    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        profile?.avatar && profile?.avatar_url
            ? [
                  {
                      id: 'avatar',
                      path: profile.avatar,
                      url: profile.avatar_url,
                      mime_type: 'image/jpeg',
                      name: profile.avatar.split('/').pop() ?? 'avatar',
                  },
              ]
            : [],
    );

    const action = ProfileController.update.form();
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
        name: profile?.name ?? user?.name ?? '',
        phone: profile?.phone ?? user?.phone ?? '',
        email: profile?.email ?? user?.email ?? '',
        avatar: null as File | null,
        remove_avatar: false,
        _method: 'patch',
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    if (!user || !profile) {
        return null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((form) => {
            const { avatar, remove_avatar, ...rest } = form;

            return {
                ...rest,
                ...(avatar ? { avatar } : {}),
                ...(remove_avatar ? { remove_avatar: true } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const isBranch = portal === 'branch';
    const page = (
        <>
            <Head title={isBranch ? 'Profile' : 'Profile settings'} />

            {!isBranch && <h1 className="sr-only">Profile settings</h1>}

            <section className="border-border/80 bg-card space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6">
                {!isBranch && (
                    <Heading
                        variant="small"
                        title="Profile"
                        description="Update your photo, name, phone number, and optional email"
                    />
                )}

                <form onSubmit={submit} className="space-y-6">
                    <div
                        className={
                            isBranch
                                ? 'block space-y-4'
                                : 'flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-4'
                        }
                    >
                        <div
                            className={
                                isBranch
                                    ? 'block w-28 space-y-1.5'
                                    : 'flex w-40 shrink-0 flex-col gap-1.5 sm:w-44'
                            }
                        >
                            <Label>Avatar</Label>
                            <FileUpload
                                variant="avatar"
                                avatarSize={isBranch ? 112 : 176}
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
                                accept={AVATAR_ACCEPT}
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

                        <div
                            className={
                                isBranch
                                    ? 'block space-y-3'
                                    : 'flex min-w-0 flex-1 flex-col gap-3'
                            }
                        >
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="phone">Phone number</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        className="mt-1 block w-full"
                                        value={data.phone}
                                        onChange={(event) =>
                                            setData(
                                                'phone',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        autoComplete="tel"
                                        placeholder="04XX XXX XXX"
                                        onBlur={() => validate('phone')}
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="email">
                                        Email address (optional)
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        value={data.email ?? ''}
                                        onChange={(event) =>
                                            setData(
                                                'email',
                                                event.target.value,
                                            )
                                        }
                                        autoComplete="email"
                                        placeholder="Email address"
                                        onBlur={(e) => {
                                            if (e.target.value.trim() !== '') {
                                                validate('email');
                                            }
                                        }}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {mustVerifyPhone && user.phone_verified_at === null && (
                        <div>
                            <p className="text-muted-foreground text-sm">
                                Your phone number is unverified.{' '}
                                <Link
                                    href={notice()}
                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                >
                                    Verify your phone
                                </Link>
                            </p>

                            {status && (
                                <div className="mt-2 text-sm font-medium text-green-600">
                                    {status}
                                </div>
                            )}
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="update-profile-button"
                        >
                            {processing ? <Spinner /> : null}
                            Save
                        </Button>
                    </div>
                </form>
            </section>

            {isBranch && passwordRules ? (
                <>
                    <ChangePasswordCard
                        title="Change Password"
                        passwordRules={passwordRules}
                    />

                    <ManageTwoFactor
                        canManageTwoFactor={canManageTwoFactor}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                    />

                    <ManagePasskeys
                        canManagePasskeys={canManagePasskeys}
                        passkeys={passkeys}
                    />
                </>
            ) : null}

            {canDeleteAccount ? <DeleteUser /> : null}
        </>
    );

    if (isBranch) {
        return (
            <BranchAccountPage
                title="Profile"
                description="Update your photo, name, phone number, and optional email"
            >
                {page}
            </BranchAccountPage>
        );
    }

    return page;
}
