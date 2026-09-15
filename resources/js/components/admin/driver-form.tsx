import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

type FormAction = {
    action: string;
    method?: string;
};

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string };

interface DriverFormDefaults {
    name?: string;
    phone?: string;
    email?: string | null;
    license_number?: string | null;
    vehicle_type?: string;
    availability_status?: string;
    notes?: string | null;
    is_active?: boolean;
    branch_ids?: number[];
}

interface DriverFormProps {
    action: FormAction;
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
    branches?: BranchOption[];
    requireBranches?: boolean;
    defaults?: DriverFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

export function DriverForm({
    action,
    vehicleTypes,
    availabilityStatuses,
    branches = [],
    requireBranches = false,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
}: DriverFormProps) {
    const [isActive, setIsActive] = useState(defaults?.is_active ?? true);
    const [vehicleType, setVehicleType] = useState(
        defaults?.vehicle_type ?? vehicleTypes[0]?.value ?? '',
    );
    const [availabilityStatus, setAvailabilityStatus] = useState(
        defaults?.availability_status ??
            availabilityStatuses.find((item) => item.value === 'offline')
                ?.value ??
            availabilityStatuses[0]?.value ??
            '',
    );
    const [branchIds, setBranchIds] = useState<number[]>(
        defaults?.branch_ids ?? [],
    );

    const toggleBranch = (id: number) => {
        setBranchIds((current) =>
            current.includes(id)
                ? current.filter((branchId) => branchId !== id)
                : [...current, id],
        );
    };

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            className="space-y-4"
            options={{ preserveScroll: true }}
        >
            {({ errors, processing }) => (
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {isEdit ? 'Edit driver' : 'Driver details'}
                        </CardTitle>
                        <CardDescription>
                            Drivers log in with phone. Assign branches so they
                            can receive deliveries later.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={defaults?.name ?? ''}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    defaultValue={defaults?.phone ?? ''}
                                    required
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={defaults?.email ?? ''}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {isEdit
                                        ? 'New password (optional)'
                                        : 'Password'}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required={!isEdit}
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required={!isEdit}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="license_number">
                                    License number
                                </Label>
                                <Input
                                    id="license_number"
                                    name="license_number"
                                    defaultValue={
                                        defaults?.license_number ?? ''
                                    }
                                />
                                <InputError message={errors.license_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Vehicle type</Label>
                                <input
                                    type="hidden"
                                    name="vehicle_type"
                                    value={vehicleType}
                                />
                                <Select
                                    value={vehicleType}
                                    onValueChange={setVehicleType}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Vehicle" />
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
                                <InputError message={errors.vehicle_type} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Availability</Label>
                                <input
                                    type="hidden"
                                    name="availability_status"
                                    value={availabilityStatus}
                                />
                                <Select
                                    value={availabilityStatus}
                                    onValueChange={setAvailabilityStatus}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Availability" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availabilityStatuses.map((status) => (
                                            <SelectItem
                                                key={status.value}
                                                value={status.value}
                                            >
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={errors.availability_status}
                                />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={defaults?.notes ?? ''}
                                    rows={3}
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </div>

                        {requireBranches ? (
                            <div className="grid gap-2">
                                <Label>Assigned branches</Label>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {branches.map((branch) => {
                                        const checked = branchIds.includes(
                                            branch.id,
                                        );

                                        return (
                                            <label
                                                key={branch.id}
                                                className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={checked}
                                                    onCheckedChange={() =>
                                                        toggleBranch(branch.id)
                                                    }
                                                />
                                                {checked ? (
                                                    <input
                                                        type="hidden"
                                                        name="branch_ids[]"
                                                        value={branch.id}
                                                    />
                                                ) : null}
                                                <span>{branch.name}</span>
                                            </label>
                                        );
                                    })}
                                </div>
                                <InputError message={errors.branch_ids} />
                            </div>
                        ) : null}

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                checked={isActive}
                                onCheckedChange={(checked) =>
                                    setIsActive(checked === true)
                                }
                            />
                            <input
                                type="hidden"
                                name="is_active"
                                value={isActive ? '1' : '0'}
                            />
                            <Label htmlFor="is_active">Active account</Label>
                        </div>
                        <InputError message={errors.is_active} />
                    </CardContent>
                    <CardFooter className="justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            {submitLabel ??
                                (isEdit ? 'Save changes' : 'Create driver')}
                        </Button>
                    </CardFooter>
                </Card>
            )}
        </Form>
    );
}
