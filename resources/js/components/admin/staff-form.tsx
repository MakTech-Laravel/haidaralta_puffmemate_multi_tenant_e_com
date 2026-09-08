import { Form, Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

type FormAction = {
    action: string;
    method?: string;
};

type RoleOption = {
    value: string;
    label: string;
};

interface StaffFormDefaults {
    name?: string;
    email?: string;
    roles?: string[];
    is_active?: boolean;
}

interface StaffFormProps {
    action: FormAction;
    roles: RoleOption[];
    defaults?: StaffFormDefaults;
    isEdit?: boolean;
    lockedSuperAdmin?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

const SUPER_ADMIN_ROLE = 'super-admin';

export function StaffForm({
    action,
    roles,
    defaults,
    isEdit = false,
    lockedSuperAdmin = false,
    cancelHref,
    submitLabel,
}: StaffFormProps) {
    const [selectedRoles, setSelectedRoles] = useState<string[]>(
        defaults?.roles ?? [],
    );
    const [isActive, setIsActive] = useState(defaults?.is_active ?? true);

    const toggleRole = (value: string) => {
        const hasRole = selectedRoles.includes(value);

        if (
            value === SUPER_ADMIN_ROLE &&
            hasRole &&
            lockedSuperAdmin
        ) {
            return;
        }

        setSelectedRoles(
            hasRole
                ? selectedRoles.filter((role) => role !== value)
                : [...selectedRoles, value],
        );
    };

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            className="space-y-6"
            options={{ preserveScroll: true }}
        >
            {({ processing, errors }) => (
                <>
                    {lockedSuperAdmin && (
                        <Alert>
                            <Lock className="h-4 w-4" />
                            <AlertTitle>Last super administrator</AlertTitle>
                            <AlertDescription>
                                This is the only active account with the
                                super-admin role. Assign the role to another
                                user before removing it or deactivating this
                                account.
                            </AlertDescription>
                        </Alert>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={defaults?.name ?? ''}
                                required
                                autoComplete="name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={defaults?.email ?? ''}
                                required
                                autoComplete="email"
                            />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Password
                                {isEdit && (
                                    <span className="text-muted-foreground ml-1 text-xs font-normal">
                                        (leave blank to keep current)
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                required={!isEdit}
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required={!isEdit}
                                autoComplete="new-password"
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Roles</Label>
                        <p className="text-muted-foreground text-xs">
                            Assign one or more roles. Permissions are inherited
                            from the selected roles.
                        </p>
                        <div className="mt-1 flex flex-wrap gap-2">
                            {roles.map((role) => {
                                const checked = selectedRoles.includes(
                                    role.value,
                                );
                                const locked =
                                    role.value === SUPER_ADMIN_ROLE &&
                                    lockedSuperAdmin &&
                                    checked;

                                return (
                                    <Label
                                        key={role.value}
                                        className={cn(
                                            'flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium capitalize transition-colors',
                                            checked
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'hover:bg-muted',
                                            locked && 'cursor-not-allowed',
                                        )}
                                    >
                                        <Checkbox
                                            checked={checked}
                                            disabled={locked}
                                            onCheckedChange={() =>
                                                toggleRole(role.value)
                                            }
                                            className="size-3.5"
                                        />
                                        {role.label}
                                        {locked && (
                                            <Lock className="h-3 w-3 opacity-70" />
                                        )}
                                    </Label>
                                );
                            })}
                        </div>
                        {selectedRoles.map((role) => (
                            <input
                                key={role}
                                type="hidden"
                                name="roles[]"
                                value={role}
                            />
                        ))}
                        <InputError
                            message={errors.roles ?? errors['roles.0']}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            type="hidden"
                            name="is_active"
                            value={isActive ? '1' : '0'}
                        />
                        <Checkbox
                            id="is_active"
                            checked={isActive}
                            disabled={lockedSuperAdmin && isActive}
                            onCheckedChange={(value) =>
                                setIsActive(value === true)
                            }
                        />
                        <Label htmlFor="is_active" className="font-normal">
                            Active
                        </Label>
                        <InputError message={errors.is_active} />
                    </div>

                    <div className="flex items-center gap-3 border-t pt-5">
                        <Button type="submit" disabled={processing}>
                            {submitLabel ??
                                (isEdit ? 'Save changes' : 'Create staff')}
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
