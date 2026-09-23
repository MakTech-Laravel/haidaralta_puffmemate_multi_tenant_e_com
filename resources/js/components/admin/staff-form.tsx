import { Link, useForm } from '@inertiajs/react';
import { Lock, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type BranchOption = {
    value: string;
    label: string;
};

interface StaffFormDefaults {
    name?: string;
    phone?: string;
    email?: string | null;
    roles?: string[];
    is_active?: boolean;
    branch_id?: string | number | null;
    avatar?: string | null;
    avatar_url?: string | null;
}

interface StaffFormProps {
    action: FormAction;
    roles?: RoleOption[];
    rolesByBranch?: Record<string, RoleOption[]>;
    branches?: BranchOption[];
    branchLocked?: boolean;
    defaults?: StaffFormDefaults;
    isEdit?: boolean;
    lockedSuperAdmin?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

const SUPER_ADMIN_ROLE = 'super-admin';
const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

function sectionClassName(): string {
    return cn(
        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
        'sm:p-6',
    );
}

function RoleCheckboxList({
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

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();

        if (q === '') {
            return roles;
        }

        return roles.filter(
            (role) =>
                role.label.toLowerCase().includes(q) ||
                role.value.toLowerCase().includes(q),
        );
    }, [query, roles]);

    return (
        <div className="flex flex-col gap-3">
            <Label>Roles</Label>
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
            <div
                role="group"
                aria-label="Roles"
                className="max-h-64 overflow-y-auto rounded-xl border"
            >
                {filtered.length === 0 ? (
                    <p className="text-muted-foreground px-3 py-8 text-center text-sm">
                        No matching roles.
                    </p>
                ) : (
                    filtered.map((role) => {
                        const isSelected = selected.includes(role.value);
                        const locked =
                            lockedSuperAdmin &&
                            role.value === SUPER_ADMIN_ROLE &&
                            isSelected;

                        return (
                            <button
                                key={role.value}
                                type="button"
                                aria-pressed={isSelected}
                                disabled={locked}
                                onClick={() => {
                                    if (!locked) {
                                        onToggle(role.value);
                                    }
                                }}
                                className={cn(
                                    'flex w-full items-center gap-2.5 border-b px-3 py-2.5 text-left text-sm last:border-b-0',
                                    isSelected
                                        ? 'bg-primary/5'
                                        : 'hover:bg-muted/40',
                                    locked && 'cursor-not-allowed opacity-60',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-4 shrink-0 items-center justify-center rounded border',
                                        isSelected
                                            ? 'border-primary bg-primary'
                                            : 'border-muted-foreground/40',
                                    )}
                                    aria-hidden
                                >
                                    {isSelected ? (
                                        <span className="text-primary-foreground text-[10px] leading-none">
                                            ✓
                                        </span>
                                    ) : null}
                                </span>
                                <span className="min-w-0 flex-1 truncate font-medium capitalize">
                                    {role.label}
                                </span>
                                {locked ? (
                                    <Lock className="text-muted-foreground size-3.5 shrink-0" />
                                ) : null}
                            </button>
                        );
                    })
                )}
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
                                ? 'border-primary bg-primary/5 ring-primary/20 ring-1'
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

export function StaffForm({
    action,
    roles = [],
    rolesByBranch,
    branches,
    branchLocked = false,
    defaults,
    isEdit = false,
    lockedSuperAdmin = false,
    cancelHref,
    submitLabel,
}: StaffFormProps) {
    const initialBranchId =
        defaults?.branch_id !== null && defaults?.branch_id !== undefined
            ? String(defaults.branch_id)
            : (branches?.[0]?.value ?? '');

    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        defaults?.avatar && defaults?.avatar_url
            ? [
                  {
                      id: 'avatar',
                      path: defaults.avatar,
                      url: defaults.avatar_url,
                      mime_type: 'image/*',
                      size: 0,
                      name: defaults.avatar.split('/').pop() ?? 'avatar',
                  },
              ]
            : [],
    );

    const formMethod = (action.method ?? 'post').toLowerCase() as
        | 'get'
        | 'post'
        | 'put'
        | 'patch'
        | 'delete';

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
        branch_id: initialBranchId,
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

    const availableRoles =
        rolesByBranch && data.branch_id
            ? (rolesByBranch[data.branch_id] ?? [])
            : roles;

    const changeBranch = (nextBranchId: string): void => {
        setData((current) => ({
            ...current,
            branch_id: nextBranchId,
            roles: [],
        }));
    };

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
                branch_id,
                ...rest
            } = form;

            return {
                ...rest,
                ...(branches && branches.length > 0 && !isEdit
                    ? { branch_id }
                    : {}),
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
        <form onSubmit={submit} className="w-full space-y-6" autoComplete="off">
            {lockedSuperAdmin ? (
                <Alert>
                    <Lock />
                    <AlertTitle>Last super administrator</AlertTitle>
                    <AlertDescription>
                        This is the only active account with the super-admin
                        role for this branch. Assign the role to another user
                        before removing it or deactivating this account.
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

                {branches && branches.length > 0 ? (
                    <div className="mb-4 flex flex-col gap-1.5">
                        <Label htmlFor="branch_id">Branch</Label>
                        <Select
                            value={data.branch_id || undefined}
                            onValueChange={changeBranch}
                            disabled={branchLocked || isEdit}
                        >
                            <SelectTrigger
                                id="branch_id"
                                className="w-full"
                                aria-invalid={Boolean(errors.branch_id)}
                            >
                                <SelectValue placeholder="Select a branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    {branches.map((branch) => (
                                        <SelectItem
                                            key={branch.value}
                                            value={branch.value}
                                        >
                                            {branch.label}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.branch_id} />
                    </div>
                ) : null}

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
                                placeholder="Full name"
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
                                    placeholder="04XX XXX XXX"
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
                                    placeholder="name@example.com"
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
                    {availableRoles.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {branches
                                ? 'Select a branch to load its roles.'
                                : 'No roles available.'}
                        </p>
                    ) : (
                        <RoleCheckboxList
                            roles={availableRoles}
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
                    {submitLabel ?? (isEdit ? 'Save changes' : 'Create staff')}
                </Button>
            </div>
        </form>
    );
}
