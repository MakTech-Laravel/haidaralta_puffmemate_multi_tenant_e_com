import { Link, useForm } from '@inertiajs/react';
import { Lock, Plus, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type RoleOption = {
    value: string;
    label: string;
};

interface AdminFormDefaults {
    name?: string;
    phone?: string;
    email?: string | null;
    roles?: string[];
    is_active?: boolean;
    avatar?: string | null;
    avatar_url?: string | null;
}

interface AdminFormProps {
    action: FormAction;
    roles?: RoleOption[];
    defaults?: AdminFormDefaults;
    isEdit?: boolean;
    lockedSuperAdmin?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

const SUPER_ADMIN_ROLE = 'super-admin';
const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

function sectionClassName(): string {
    return cn(
        'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
        'sm:p-6',
    );
}

function RoleAssignmentPicker({
    roles,
    selected,
    onToggle,
    lockedSuperAdmin,
}: {
    roles: RoleOption[];
    selected: string[];
    onToggle: (value: string) => void;
    lockedSuperAdmin: boolean;
}) {
    const [query, setQuery] = useState('');
    const selectedSet = useMemo(() => new Set(selected), [selected]);

    const available = useMemo(() => {
        const q = query.trim().toLowerCase();

        return roles.filter((role) => {
            if (selectedSet.has(role.value)) {
                return false;
            }

            if (q === '') {
                return true;
            }

            return (
                role.label.toLowerCase().includes(q) ||
                role.value.toLowerCase().includes(q)
            );
        });
    }, [query, roles, selectedSet]);

    const assigned = useMemo(
        () => roles.filter((role) => selectedSet.has(role.value)),
        [roles, selectedSet],
    );

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between gap-3">
                <Label>Roles</Label>
                <p className="text-muted-foreground text-xs">
                    {selected.length} assigned
                </p>
            </div>
            <div className="relative">
                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search roles…"
                    className="pl-9"
                    autoComplete="off"
                />
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <div className="overflow-hidden rounded-xl border">
                    <div className="bg-muted/40 text-muted-foreground border-b px-3 py-2 text-xs font-medium tracking-wide uppercase">
                        Available
                    </div>
                    <div className="max-h-64 overflow-y-auto">
                        {available.length === 0 ? (
                            <p className="text-muted-foreground px-3 py-8 text-center text-sm">
                                {query.trim() === ''
                                    ? 'All roles are assigned.'
                                    : 'No matching roles.'}
                            </p>
                        ) : (
                            available.map((role) => (
                                <button
                                    key={role.value}
                                    type="button"
                                    onClick={() => onToggle(role.value)}
                                    className="hover:bg-muted/50 flex w-full items-center justify-between gap-2 border-b px-3 py-2.5 text-left text-sm last:border-b-0"
                                >
                                    <span className="min-w-0 truncate font-medium">
                                        {role.label}
                                    </span>
                                    <Plus className="text-muted-foreground size-4 shrink-0" />
                                </button>
                            ))
                        )}
                    </div>
                </div>
                <div className="overflow-hidden rounded-xl border">
                    <div className="bg-muted/40 text-muted-foreground border-b px-3 py-2 text-xs font-medium tracking-wide uppercase">
                        Assigned
                    </div>
                    <div className="max-h-64 overflow-y-auto">
                        {assigned.length === 0 ? (
                            <p className="text-muted-foreground px-3 py-8 text-center text-sm">
                                Assign at least one role.
                            </p>
                        ) : (
                            assigned.map((role) => {
                                const locked =
                                    (role.value === SUPER_ADMIN_ROLE &&
                                        lockedSuperAdmin) ||
                                    selected.length === 1;

                                return (
                                    <div
                                        key={role.value}
                                        className="flex items-center justify-between gap-2 border-b px-3 py-2.5 last:border-b-0"
                                    >
                                        <span className="min-w-0 truncate text-sm font-medium">
                                            {role.label}
                                        </span>
                                        {locked ? (
                                            <Lock className="text-muted-foreground size-3.5 shrink-0" />
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    onToggle(role.value)
                                                }
                                                className="text-muted-foreground hover:text-destructive rounded-md p-0.5"
                                                aria-label={`Remove ${role.label}`}
                                            >
                                                <X className="size-4" />
                                            </button>
                                        )}
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function AccountStatusCards({
    value,
    onChange,
    disabled,
}: {
    value: boolean;
    onChange: (active: boolean) => void;
    disabled?: boolean;
}) {
    const options = [
        { active: true, label: 'Active' },
        { active: false, label: 'Inactive' },
    ] as const;

    return (
        <div
            role="radiogroup"
            aria-label="Account status"
            className="grid grid-cols-2 gap-3"
        >
            {options.map((option) => {
                const selected = value === option.active;
                const locked = disabled && !option.active;

                return (
                    <button
                        key={option.label}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        disabled={locked}
                        onClick={() => {
                            if (!locked) {
                                onChange(option.active);
                            }
                        }}
                        className={cn(
                            'flex min-h-11 items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left text-sm font-medium transition-colors',
                            selected
                                ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                : 'border-border/80 bg-card hover:bg-muted/40',
                            locked && 'cursor-not-allowed opacity-60',
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

export function AdminForm({
    action,
    roles = [],
    defaults,
    isEdit = false,
    lockedSuperAdmin = false,
    cancelHref,
    submitLabel,
}: AdminFormProps) {
    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        defaults?.avatar && defaults?.avatar_url
            ? [
                  {
                      id: 'avatar',
                      path: defaults.avatar,
                      url: defaults.avatar_url,
                      size: 0,
                      name: defaults.avatar.split('/').pop() ?? 'avatar',
                  },
              ]
            : [],
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
        progress,
        transform,
        validate,
        setValidationTimeout,
        cancel,
    } = useForm(formMethod, action.action, {
        name: defaults?.name ?? '',
        phone: defaults?.phone ?? '',
        email: defaults?.email ?? '',
        password: '',
        password_confirmation: '',
        roles: defaults?.roles ?? [],
        is_active: defaults?.is_active ?? true,
        avatar: null as File | null,
        remove_avatar: false,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const toggleRole = (value: string): void => {
        const hasRole = data.roles.includes(value);

        if (value === SUPER_ADMIN_ROLE && hasRole && lockedSuperAdmin) {
            return;
        }

        setData(
            'roles',
            hasRole
                ? data.roles.filter((role) => role !== value)
                : [...data.roles, value],
        );
    };

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
        <form
            onSubmit={submit}
            className="w-full space-y-6"
            autoComplete="off"
        >
            {lockedSuperAdmin ? (
                <Alert>
                    <Lock />
                    <AlertTitle>Last super administrator</AlertTitle>
                    <AlertDescription>
                        This is the only active account with the super-admin
                        role. Assign the role to another user before removing it
                        or deactivating this account.
                    </AlertDescription>
                </Alert>
            ) : null}

            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Account
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Contact details, avatar, and login password.
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
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                autoComplete="off"
                                placeholder="Ava Mitchell"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    autoComplete="off"
                                    placeholder="0412 345 678"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    onBlur={() => validate('phone')}
                                    required
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="email">
                                    Email{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="off"
                                    placeholder="name@puffmemate.com"
                                    value={data.email ?? ''}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    onBlur={() => {
                                        if (data.email) {
                                            validate('email');
                                        }
                                    }}
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password">
                                    {isEdit
                                        ? 'New password (optional)'
                                        : 'Password'}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    autoComplete="new-password"
                                    placeholder="Choose a password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    required={!isEdit}
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    autoComplete="new-password"
                                    placeholder="Type password again"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    required={!isEdit}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className={sectionClassName()}>
                <div className="mb-5 space-y-1">
                    <h2 className="text-base font-semibold tracking-tight">
                        Access
                    </h2>
                    <p className="text-muted-foreground text-sm">
                        Assign one or more roles. Permissions are inherited from
                        the selected roles.
                    </p>
                </div>

                <div className="flex flex-col gap-5">
                    {roles.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No roles available.
                        </p>
                    ) : (
                        <RoleAssignmentPicker
                            roles={roles}
                            selected={data.roles}
                            onToggle={toggleRole}
                            lockedSuperAdmin={lockedSuperAdmin}
                        />
                    )}
                    <InputError message={errors.roles ?? errors['roles.0']} />

                    <div className="flex flex-col gap-2">
                        <Label>Status</Label>
                        <AccountStatusCards
                            value={data.is_active}
                            disabled={lockedSuperAdmin && data.is_active}
                            onChange={(active) => setData('is_active', active)}
                        />
                        <InputError message={errors.is_active} />
                    </div>
                </div>
            </section>

            <div className="flex justify-end gap-2">
                <Button variant="outline" asChild>
                    <Link href={cancelHref}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : null}
                    {submitLabel ??
                        (isEdit ? 'Save changes' : 'Create admin')}
                </Button>
            </div>
        </form>
    );
}
