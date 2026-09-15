import { Link, useForm } from '@inertiajs/react';
import { Building2, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useState } from 'react';
import { DriverBranchAssignSheet } from '@/components/admin/driver-branch-assign-sheet';
import FileUpload, { type ExistingFile } from '@/components/file-upload';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { TimePicker } from '@/components/ui/time-picker';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string; code?: string | null };

type RosterDraft = {
    key: string;
    branch_id: string;
    date: string;
    start_time: string;
    end_time: string;
    status: string;
};

interface DriverFormDefaults {
    name?: string;
    phone?: string;
    email?: string | null;
    license_number?: string | null;
    vehicle_type?: string;
    availability_status?: string;
    notes?: string | null;
    address?: string | null;
    is_active?: boolean;
    branch_ids?: number[];
    avatar?: string | null;
    avatar_url?: string | null;
    vehicle_image?: string | null;
    vehicle_image_url?: string | null;
}

interface DriverFormProps {
    action: FormAction;
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
    rosterStatuses?: Option[];
    branches?: BranchOption[];
    requireBranches?: boolean;
    defaults?: DriverFormDefaults;
    isEdit?: boolean;
    cancelHref: string;
    submitLabel?: string;
}

const MEDIA_ACCEPT = 'image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp';

function sectionClassName(): string {
    return cn(
        'rounded-2xl border border-border/80 bg-card p-5 shadow-sm',
        'sm:p-6',
    );
}

export function DriverForm({
    action,
    vehicleTypes,
    availabilityStatuses,
    rosterStatuses = [],
    branches = [],
    requireBranches = false,
    defaults,
    isEdit = false,
    cancelHref,
    submitLabel,
}: DriverFormProps) {
    const [branchSheetOpen, setBranchSheetOpen] = useState(false);
    const [existingAvatar, setExistingAvatar] = useState<ExistingFile[]>(() =>
        defaults?.avatar && defaults?.avatar_url
            ? [
                  {
                      id: 'avatar',
                      path: defaults.avatar,
                      url: defaults.avatar_url,
                      mime_type: 'image/jpeg',
                      name: defaults.avatar.split('/').pop() ?? 'avatar',
                  },
              ]
            : [],
    );
    const [existingVehicle, setExistingVehicle] = useState<ExistingFile[]>(
        () =>
            defaults?.vehicle_image && defaults?.vehicle_image_url
                ? [
                      {
                          id: 'vehicle',
                          path: defaults.vehicle_image,
                          url: defaults.vehicle_image_url,
                          mime_type: 'image/jpeg',
                          name:
                              defaults.vehicle_image.split('/').pop() ??
                              'vehicle',
                      },
                  ]
                : [],
    );
    const [rosters, setRosters] = useState<RosterDraft[]>([]);
    const [extraBranches, setExtraBranches] = useState<BranchOption[]>([]);

    const formMethod = (
        action.method ?? 'post'
    ).toLowerCase() as 'get' | 'post' | 'put' | 'patch' | 'delete';

    const defaultRosterStatus =
        rosterStatuses.find((item) => item.value === 'scheduled')?.value ??
        rosterStatuses[0]?.value ??
        'scheduled';

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
        license_number: defaults?.license_number ?? '',
        vehicle_type: defaults?.vehicle_type ?? vehicleTypes[0]?.value ?? '',
        availability_status:
            defaults?.availability_status ??
            availabilityStatuses.find((item) => item.value === 'offline')
                ?.value ??
            availabilityStatuses[0]?.value ??
            '',
        notes: defaults?.notes ?? '',
        address: defaults?.address ?? '',
        is_active: defaults?.is_active ?? true,
        branch_ids: defaults?.branch_ids ?? [],
        avatar: null as File | null,
        vehicle_image: null as File | null,
        remove_avatar: false,
        remove_vehicle_image: false,
        rosters: [] as Array<{
            branch_id: number;
            date: string;
            start_time: string;
            end_time: string;
            status: string;
        }>,
        ...(isEdit ? { _method: 'put' } : {}),
    });

    useEffect(() => {
        setValidationTimeout(500);
    }, [setValidationTimeout]);

    const branchLookup = useMemo(() => {
        const map = new Map<number, BranchOption>();

        for (const branch of [...branches, ...extraBranches]) {
            map.set(branch.id, branch);
        }

        return map;
    }, [branches, extraBranches]);

    const assignedBranches = data.branch_ids
        .map((id) => branchLookup.get(id))
        .filter((branch): branch is BranchOption => branch !== undefined);

    const submit = (event: FormEvent): void => {
        event.preventDefault();

        transform((form) => {
            const {
                avatar,
                vehicle_image,
                remove_avatar,
                remove_vehicle_image,
                password,
                password_confirmation,
                rosters: _ignored,
                ...rest
            } = form;

            const rosterPayload = rosters
                .filter(
                    (row) =>
                        row.branch_id &&
                        row.date &&
                        row.start_time &&
                        row.end_time,
                )
                .map((row) => ({
                    branch_id: Number(row.branch_id),
                    date: row.date,
                    start_time: row.start_time,
                    end_time: row.end_time,
                    status: row.status || defaultRosterStatus,
                }));

            return {
                ...rest,
                ...(password ? { password, password_confirmation } : {}),
                ...(avatar ? { avatar } : {}),
                ...(vehicle_image ? { vehicle_image } : {}),
                ...(remove_avatar ? { remove_avatar: true } : {}),
                ...(remove_vehicle_image ? { remove_vehicle_image: true } : {}),
                ...(rosterPayload.length > 0 ? { rosters: rosterPayload } : {}),
            };
        });

        post(action.action, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <form onSubmit={submit} className="space-y-6">
                <section className={sectionClassName()}>
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Driver details
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Account contact, avatar, and login password.
                        </p>
                    </div>

                    <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                        <div className="flex shrink-0 flex-col gap-1.5">
                            <Label>Avatar</Label>
                            <FileUpload
                                variant="avatar"
                                avatarSize={112}
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
                                placeholder="Photo"
                                hint="PNG, JPG, or WebP · up to 2MB"
                                error={errors.avatar}
                            />
                            <InputError message={errors.avatar} />
                        </div>

                        <div className="grid min-w-0 flex-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    onBlur={() => validate('phone')}
                                    required
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="off"
                                    value={data.email}
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
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {isEdit
                                        ? 'New password (optional)'
                                        : 'Password'}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
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
                </section>

                <section className={sectionClassName()}>
                    <div className="mb-5 space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            Profile & vehicle
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            License, vehicle image, address, and availability
                            override.
                        </p>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-[14rem_minmax(0,1fr)]">
                        <div className="grid gap-2">
                            <Label>Vehicle image</Label>
                            <FileUpload
                                value={data.vehicle_image}
                                onChange={(file) => {
                                    const next = Array.isArray(file)
                                        ? (file[0] ?? null)
                                        : file;
                                    setExistingVehicle([]);
                                    setData((current) => ({
                                        ...current,
                                        vehicle_image: next,
                                        remove_vehicle_image: next
                                            ? false
                                            : current.remove_vehicle_image,
                                    }));
                                }}
                                existingFiles={
                                    data.vehicle_image ||
                                    data.remove_vehicle_image
                                        ? []
                                        : existingVehicle
                                }
                                onRemoveExisting={() => {
                                    setExistingVehicle([]);
                                    setData((current) => ({
                                        ...current,
                                        vehicle_image: null,
                                        remove_vehicle_image: true,
                                    }));
                                }}
                                accept={MEDIA_ACCEPT}
                                maxSize={2}
                                maxFiles={1}
                                multiple={false}
                                lightbox
                                isUploading={processing}
                                uploadProgress={progress?.percentage ?? null}
                                onCancel={processing ? cancel : undefined}
                                placeholder="Add vehicle photo"
                                hint="PNG, JPG, or WebP · up to 2MB"
                                error={errors.vehicle_image}
                                classNames={{
                                    wrapper: 'w-full',
                                    dropzone:
                                        'min-h-40 border-dashed border-border/60 bg-muted/30',
                                }}
                            />
                            <InputError message={errors.vehicle_image} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="license_number">
                                    License number
                                </Label>
                                <Input
                                    id="license_number"
                                    value={data.license_number}
                                    onChange={(event) =>
                                        setData(
                                            'license_number',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.license_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Vehicle type</Label>
                                <Select
                                    value={data.vehicle_type}
                                    onValueChange={(value) =>
                                        setData('vehicle_type', value)
                                    }
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
                                <Label>Availability override</Label>
                                <Select
                                    value={data.availability_status}
                                    onValueChange={(value) =>
                                        setData('availability_status', value)
                                    }
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
                            <div className="flex items-end gap-2 pb-1">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label htmlFor="is_active">
                                    Active account
                                </Label>
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    value={data.address}
                                    onChange={(event) =>
                                        setData('address', event.target.value)
                                    }
                                    rows={3}
                                />
                                <InputError message={errors.address} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="notes">Notes</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(event) =>
                                        setData('notes', event.target.value)
                                    }
                                    rows={3}
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </div>
                    </div>
                </section>

                {requireBranches ? (
                    <section className={sectionClassName()}>
                        <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-1">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Assigned branches
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    At least one branch is required for platform
                                    drivers.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setBranchSheetOpen(true)}
                            >
                                <Building2 className="size-4" />
                                Manage branches
                            </Button>
                        </div>

                        {assignedBranches.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No branches selected yet.
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-1.5">
                                {assignedBranches.map((branch) => (
                                    <Badge key={branch.id} variant="secondary">
                                        {branch.name}
                                    </Badge>
                                ))}
                            </div>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.branch_ids}
                        />
                    </section>
                ) : null}

                {requireBranches && rosterStatuses.length > 0 ? (
                    <section className={sectionClassName()}>
                        <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-1">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Duty shifts
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Optional initial roster rows for assigned
                                    branches.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={assignedBranches.length === 0}
                                onClick={() =>
                                    setRosters((current) => [
                                        ...current,
                                        {
                                            key: crypto.randomUUID(),
                                            branch_id: String(
                                                assignedBranches[0]?.id ?? '',
                                            ),
                                            date: '',
                                            start_time: '',
                                            end_time: '',
                                            status: defaultRosterStatus,
                                        },
                                    ])
                                }
                            >
                                <Plus className="size-4" />
                                Add shift
                            </Button>
                        </div>

                        {rosters.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No duty shifts added.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {rosters.map((row, index) => (
                                    <div
                                        key={row.key}
                                        className="grid gap-3 rounded-xl border p-3 md:grid-cols-6"
                                    >
                                        <div className="grid gap-2 md:col-span-2">
                                            <Label>Branch</Label>
                                            <Select
                                                value={row.branch_id}
                                                onValueChange={(value) =>
                                                    setRosters((current) =>
                                                        current.map((item) =>
                                                            item.key === row.key
                                                                ? {
                                                                      ...item,
                                                                      branch_id:
                                                                          value,
                                                                  }
                                                                : item,
                                                        ),
                                                    )
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Branch" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {assignedBranches.map(
                                                        (branch) => (
                                                            <SelectItem
                                                                key={branch.id}
                                                                value={String(
                                                                    branch.id,
                                                                )}
                                                            >
                                                                {branch.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Date</Label>
                                            <DatePicker
                                                value={row.date}
                                                onChange={(value) =>
                                                    setRosters((current) =>
                                                        current.map((item) =>
                                                            item.key === row.key
                                                                ? {
                                                                      ...item,
                                                                      date: value,
                                                                  }
                                                                : item,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Start</Label>
                                            <TimePicker
                                                value={row.start_time}
                                                onChange={(value) =>
                                                    setRosters((current) =>
                                                        current.map((item) =>
                                                            item.key === row.key
                                                                ? {
                                                                      ...item,
                                                                      start_time:
                                                                          value,
                                                                  }
                                                                : item,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>End</Label>
                                            <TimePicker
                                                value={row.end_time}
                                                onChange={(value) =>
                                                    setRosters((current) =>
                                                        current.map((item) =>
                                                            item.key === row.key
                                                                ? {
                                                                      ...item,
                                                                      end_time:
                                                                          value,
                                                                  }
                                                                : item,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                        <div className="flex items-end justify-between gap-2">
                                            <div className="grid min-w-0 flex-1 gap-2">
                                                <Label>Status</Label>
                                                <Select
                                                    value={row.status}
                                                    onValueChange={(value) =>
                                                        setRosters((current) =>
                                                            current.map(
                                                                (item) =>
                                                                    item.key ===
                                                                    row.key
                                                                        ? {
                                                                              ...item,
                                                                              status: value,
                                                                          }
                                                                        : item,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {rosterStatuses.map(
                                                            (status) => (
                                                                <SelectItem
                                                                    key={
                                                                        status.value
                                                                    }
                                                                    value={
                                                                        status.value
                                                                    }
                                                                >
                                                                    {
                                                                        status.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive"
                                                aria-label={`Remove shift ${index + 1}`}
                                                onClick={() =>
                                                    setRosters((current) =>
                                                        current.filter(
                                                            (item) =>
                                                                item.key !==
                                                                row.key,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                ) : null}

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href={cancelHref}>Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? <Spinner /> : null}
                        {submitLabel ??
                            (isEdit ? 'Save changes' : 'Create driver')}
                    </Button>
                </div>
            </form>

            {requireBranches ? (
                <DriverBranchAssignSheet
                    open={branchSheetOpen}
                    onOpenChange={setBranchSheetOpen}
                    selectedIds={data.branch_ids}
                    onConfirm={(branchIds, selectedBranches) => {
                        setExtraBranches(selectedBranches);
                        setData('branch_ids', branchIds);
                    }}
                />
            ) : null}
        </>
    );
}
