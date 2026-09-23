import { Link, useForm } from '@inertiajs/react';
import { Lock, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
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

type SelectedBranch = {
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
    branchOptionsUrl?: string | null;
    roleOptionsUrl?: string | null;
    selectedBranch?: SelectedBranch | null;
    branchLocked?: boolean;
    defaults?: StaffFormDefaults;
    isEdit?: boolean;
    lockedSuperAdmin?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

const SUPER_ADMIN_ROLE = 'super-admin';
const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

function RequiredMark() {
    return (
        <span className="text-primary" aria-hidden>
            *
        </span>
    );
}

function sectionClassName(): string {
    return cn(
        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
        'sm:p-6',
    );
}

function RoleRadioList({
    roles,
    selected,
    onSelect,
    lockedSuperAdmin,
}: {
    roles: RoleOption[];
    selected: string | null;
    onSelect: (value: string) => void;
    lockedSuperAdmin: boolean;
}) {
    const [query, setQuery] = useState('');
    const locked = lockedSuperAdmin && selected === SUPER_ADMIN_ROLE;

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
            <Label>
                Role <RequiredMark />
            </Label>
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
                role="radiogroup"
                aria-label="Role"
                className="max-h-64 overflow-y-auto rounded-xl border"
            >
                {filtered.length === 0 ? (
                    <p className="text-muted-foreground px-3 py-8 text-center text-sm">
                        No matching roles.
                    </p>
                ) : (
                    filtered.map((role) => {
                        const isSelected = selected === role.value;
                        const disabled =
                            locked && role.value !== SUPER_ADMIN_ROLE;

                        return (
                            <button
                                key={role.value}
                                type="button"
                                role="radio"
                                aria-checked={isSelected}
                                disabled={disabled}
                                onClick={() => {
                                    if (!disabled) {
                                        onSelect(role.value);
                                    }
                                }}
                                className={cn(
                                    'flex w-full items-center gap-2.5 border-b px-3 py-2.5 text-left text-sm last:border-b-0',
                                    isSelected
                                        ? 'bg-primary/5'
                                        : 'hover:bg-muted/40',
                                    disabled && 'cursor-not-allowed opacity-60',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                        isSelected
                                            ? 'border-primary'
                                            : 'border-muted-foreground/40',
                                    )}
                                    aria-hidden
                                >
                                    {isSelected ? (
                                        <span className="bg-primary size-2 rounded-full" />
                                    ) : null}
                                </span>
                                <span className="min-w-0 flex-1 truncate font-medium capitalize">
                                    {role.label}
                                </span>
                                {locked && isSelected ? (
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
    branchOptionsUrl = null,
    roleOptionsUrl = null,
    selectedBranch = null,
    branchLocked = false,
    defaults,
    isEdit = false,
    lockedSuperAdmin = false,
    cancelHref,
    submitLabel,
}: StaffFormProps) {
    const showBranchPicker = Boolean(branchOptionsUrl) || Boolean(selectedBranch);
    const initialBranchId =
        defaults?.branch_id !== null && defaults?.branch_id !== undefined
            ? String(defaults.branch_id)
            : (selectedBranch?.value ?? '');

    const [branchLabel, setBranchLabel] = useState<string | null>(
        selectedBranch?.label ?? null,
    );
    const [availableRoles, setAvailableRoles] = useState<RoleOption[]>(roles);
    const [rolesLoading, setRolesLoading] = useState(false);

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
        clearErrors,
        cancel,
    } = useForm(formMethod, action.action, {
        branch_id: initialBranchId,
        name: defaults?.name ?? '',
        phone: defaults?.phone ?? '',
        email: defaults?.email ?? '',
        password: '',
        password_confirmation: '',
        roles: defaults?.roles?.slice(0, 1) ?? [],
        is_active: defaults?.is_active ?? true,
        avatar: null as File | null,
        remove_avatar: false,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    useEffect(() => {
        setAvailableRoles(roles);
    }, [roles]);

    useEffect(() => {
        if (!roleOptionsUrl || !data.branch_id || roles.length > 0) {
            return;
        }

        const controller = new AbortController();

        setRolesLoading(true);

        const url = new URL(roleOptionsUrl, window.location.origin);
        url.searchParams.set('branch_id', data.branch_id);

        void fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Failed to load roles');
                }

                return (await response.json()) as { data: RoleOption[] };
            })
            .then((payload) => {
                setAvailableRoles(payload.data);
            })
            .catch((error: Error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                setAvailableRoles([]);
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setRolesLoading(false);
                }
            });

        return () => controller.abort();
    }, [data.branch_id, roleOptionsUrl, roles.length]);

    const validatePhone = (): void => {
        validate('phone');
    };

    const validateEmail = (value: string): void => {
        if (value.trim() === '') {
            clearErrors('email');

            return;
        }

        validate('email');
    };

    const changeBranch = (nextBranchId: string | null, label?: string): void => {
        setBranchLabel(label ?? null);
        setAvailableRoles([]);
        setData((current) => ({
            ...current,
            branch_id: nextBranchId ?? '',
            roles: [],
        }));
    };

    const selectRole = (value: string): void => {
        if (
            lockedSuperAdmin &&
            data.roles.includes(SUPER_ADMIN_ROLE) &&
            value !== SUPER_ADMIN_ROLE
        ) {
            return;
        }

        setData('roles', [value]);
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
                ...(showBranchPicker && !isEdit ? { branch_id } : {}),
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

    const branchPickerDisabled = branchLocked || isEdit;

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

                {showBranchPicker ? (
                    <div className="mb-4 flex flex-col gap-1.5">
                        <Label htmlFor="branch_id">
                            Branch <RequiredMark />
                        </Label>
                        {branchOptionsUrl && !branchPickerDisabled ? (
                            <InfiniteScrollCombobox
                                id="branch_id"
                                optionsUrl={branchOptionsUrl}
                                value={data.branch_id || null}
                                onValueChange={(next, option) => {
                                    changeBranch(next, option?.label);
                                }}
                                selectedLabel={branchLabel}
                                placeholder="Select a branch"
                                searchPlaceholder="Search branches…"
                                emptyText="No branches found."
                                descriptionKey={null}
                                className={
                                    errors.branch_id
                                        ? 'border-destructive'
                                        : undefined
                                }
                            />
                        ) : (
                            <InfiniteScrollCombobox
                                id="branch_id"
                                staticOptions={
                                    selectedBranch
                                        ? [
                                              {
                                                  value: selectedBranch.value,
                                                  label: selectedBranch.label,
                                              },
                                          ]
                                        : []
                                }
                                value={data.branch_id || null}
                                onValueChange={() => undefined}
                                selectedLabel={
                                    branchLabel ?? selectedBranch?.label
                                }
                                placeholder="Select a branch"
                                searchPlaceholder="Search branches…"
                                emptyText="No branches found."
                                disabled
                            />
                        )}
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
                            <Label htmlFor="name">
                                Name <RequiredMark />
                            </Label>
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
                                <Label htmlFor="phone">
                                    Phone <RequiredMark />
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    autoComplete="off"
                                    placeholder="04XX XXX XXX"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    onBlur={validatePhone}
                                    aria-invalid={Boolean(errors.phone)}
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
                                    onBlur={(event) =>
                                        validateEmail(event.target.value)
                                    }
                                    aria-invalid={Boolean(errors.email)}
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="password">
                                    {isEdit ? (
                                        'New password (optional)'
                                    ) : (
                                        <>
                                            Password <RequiredMark />
                                        </>
                                    )}
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
                                    {isEdit ? (
                                        'Confirm password'
                                    ) : (
                                        <>
                                            Confirm password <RequiredMark />
                                        </>
                                    )}
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
                        Assign one role. Permissions are inherited from that
                        role.
                    </p>
                </div>

                <div className="flex flex-col gap-5">
                    {rolesLoading ? (
                        <p className="text-muted-foreground flex items-center gap-2 text-sm">
                            <Spinner />
                            Loading roles…
                        </p>
                    ) : availableRoles.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {showBranchPicker && !data.branch_id
                                ? 'Select a branch to load its roles.'
                                : 'No roles available.'}
                        </p>
                    ) : (
                        <RoleRadioList
                            roles={availableRoles}
                            selected={data.roles[0] ?? null}
                            onSelect={selectRole}
                            lockedSuperAdmin={lockedSuperAdmin}
                        />
                    )}
                    <InputError message={errors.roles ?? errors['roles.0']} />

                    <div className="flex flex-col gap-2">
                        <Label>
                            Status <RequiredMark />
                        </Label>
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
