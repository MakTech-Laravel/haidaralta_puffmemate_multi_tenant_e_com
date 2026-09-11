import { Form, Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
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

type BranchOption = {
    value: string;
    label: string;
};

interface StaffFormDefaults {
    name?: string;
    email?: string;
    roles?: string[];
    is_active?: boolean;
    branch_id?: string | number | null;
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

    const [branchId, setBranchId] = useState(initialBranchId);
    const [selectedRoles, setSelectedRoles] = useState<string[]>(
        defaults?.roles ?? [],
    );
    const [isActive, setIsActive] = useState(defaults?.is_active ?? true);

    const availableRoles =
        rolesByBranch && branchId ? (rolesByBranch[branchId] ?? []) : roles;

    const toggleRole = (value: string) => {
        const hasRole = selectedRoles.includes(value);

        if (value === SUPER_ADMIN_ROLE && hasRole && lockedSuperAdmin) {
            return;
        }

        setSelectedRoles(
            hasRole
                ? selectedRoles.filter((role) => role !== value)
                : [...selectedRoles, value],
        );
    };

    const changeBranch = (nextBranchId: string) => {
        setBranchId(nextBranchId);
        setSelectedRoles([]);
    };

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            options={{ preserveScroll: true }}
            validationTimeout={500}
        >
            {({ processing, errors, validate }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {isEdit ? 'Staff details' : 'Account details'}
                        </CardTitle>
                        <CardDescription>
                            {isEdit
                                ? 'Update profile, password, roles, and status.'
                                : 'Fill in the account details, then assign roles.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-6">
                        {lockedSuperAdmin ? (
                            <Alert>
                                <Lock />
                                <AlertTitle>
                                    Last super administrator
                                </AlertTitle>
                                <AlertDescription>
                                    This is the only active account with the
                                    super-admin role. Assign the role to another
                                    user before removing it or deactivating this
                                    account.
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {branches && branches.length > 0 ? (
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="branch_id">Branch</Label>
                                <input
                                    type="hidden"
                                    name="branch_id"
                                    value={branchId}
                                />
                                <Select
                                    value={branchId || undefined}
                                    onValueChange={changeBranch}
                                    disabled={branchLocked || isEdit}
                                >
                                    <SelectTrigger
                                        id="branch_id"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.branch_id,
                                        )}
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

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={defaults?.name ?? ''}
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={defaults?.email ?? ''}
                                    required
                                    autoComplete="email"
                                    placeholder="name@example.com"
                                    aria-invalid={Boolean(errors.email)}
                                    onBlur={() => validate('email')}
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="password">
                                    Password
                                    {isEdit ? (
                                        <span className="text-muted-foreground ml-1 text-xs font-normal">
                                            (leave blank to keep current)
                                        </span>
                                    ) : null}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required={!isEdit}
                                    autoComplete="new-password"
                                    placeholder={
                                        isEdit
                                            ? 'New password (optional)'
                                            : 'Enter password'
                                    }
                                    aria-invalid={Boolean(errors.password)}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required={!isEdit}
                                    autoComplete="new-password"
                                    placeholder={
                                        isEdit
                                            ? 'Confirm new password'
                                            : 'Confirm password'
                                    }
                                />
                            </div>
                        </div>

                        <Separator />

                        <div className="flex flex-col gap-2">
                            <Label>Roles</Label>
                            <p className="text-muted-foreground text-xs">
                                Assign one or more roles. Permissions are
                                inherited from the selected roles.
                            </p>
                            {availableRoles.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    {branches
                                        ? 'Select a branch to load its roles.'
                                        : 'No roles available.'}
                                </p>
                            ) : (
                                <div className="mt-1 flex flex-wrap gap-2">
                                    {availableRoles.map((role) => {
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
                                                    'flex cursor-pointer items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium capitalize transition-colors',
                                                    checked
                                                        ? 'border-primary bg-primary/10 text-primary'
                                                        : 'hover:bg-muted',
                                                    locked &&
                                                        'cursor-not-allowed',
                                                )}
                                            >
                                                <Checkbox
                                                    checked={checked}
                                                    disabled={locked}
                                                    onCheckedChange={() =>
                                                        toggleRole(role.value)
                                                    }
                                                />
                                                {role.label}
                                                {locked ? <Lock /> : null}
                                            </Label>
                                        );
                                    })}
                                </div>
                            )}
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
                    </CardContent>
                    <CardFooter className="flex items-center gap-3 border-t pt-6">
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            {submitLabel ??
                                (isEdit ? 'Save changes' : 'Create staff')}
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </CardFooter>
                </Card>
            )}
        </Form>
    );
}
