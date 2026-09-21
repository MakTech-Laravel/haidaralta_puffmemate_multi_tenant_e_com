import { useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { BranchOption, Option } from '@/components/admin/workforce-types';

type FormAction = {
    action: string;
    method?: string;
};

type TransferEmployeeSheetProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    action: FormAction;
    subjectType: 'driver' | 'branch_staff';
    branches: BranchOption[];
    currentBranchId?: number | null;
    rolesByBranch?: Record<string, Option[]>;
    currentRoles?: string[];
};

export function TransferEmployeeSheet({
    open,
    onOpenChange,
    action,
    subjectType,
    branches,
    currentBranchId = null,
    rolesByBranch = {},
    currentRoles = [],
}: TransferEmployeeSheetProps) {
    const isStaff = subjectType === 'branch_staff';
    const form = useForm({
        to_branch_id: currentBranchId ? String(currentBranchId) : '',
        effective_date: '',
        reason: '',
        roles: currentRoles,
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        form.setData({
            to_branch_id: currentBranchId ? String(currentBranchId) : '',
            effective_date: '',
            reason: '',
            roles: currentRoles,
        });
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, currentBranchId, currentRoles.join('|')]);

    const destinationRoles = useMemo(
        () => rolesByBranch[form.data.to_branch_id] ?? [],
        [form.data.to_branch_id, rolesByBranch],
    );

    const submit = (): void => {
        const method = (action.method ?? 'post').toLowerCase();

        form.transform((data) => {
            if (isStaff) {
                return data;
            }

            const { roles: _roles, ...rest } = data;

            return rest;
        });

        if (method === 'put') {
            form.put(action.action, {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });

            return;
        }

        form.post(action.action, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex flex-col gap-6 sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>
                        {isStaff ? 'Transfer staff' : 'Transfer driver'}
                    </SheetTitle>
                    <SheetDescription>
                        {isStaff
                            ? 'Move this person to another branch, optionally with a new role. User type never changes.'
                            : 'Move this driver to another branch. Drivers keep the driver role.'}
                    </SheetDescription>
                </SheetHeader>

                <div className="grid gap-4">
                    <div className="flex flex-col gap-1.5">
                        <Label>Destination branch</Label>
                        <Select
                            value={form.data.to_branch_id}
                            onValueChange={(value) => {
                                form.setData('to_branch_id', value);
                                if (isStaff) {
                                    form.setData(
                                        'roles',
                                        rolesByBranch[value]?.some((role) =>
                                            currentRoles.includes(role.value),
                                        )
                                            ? currentRoles
                                            : [],
                                    );
                                }
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select branch" />
                            </SelectTrigger>
                            <SelectContent>
                                {branches.map((branch) => (
                                    <SelectItem
                                        key={branch.id}
                                        value={String(branch.id)}
                                    >
                                        {branch.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.to_branch_id} />
                    </div>

                    {isStaff ? (
                        <div className="flex flex-col gap-1.5">
                            <Label>Role at destination</Label>
                            <Select
                                value={form.data.roles[0] ?? ''}
                                onValueChange={(value) =>
                                    form.setData('roles', value ? [value] : [])
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Keep current role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {destinationRoles.map((role) => (
                                        <SelectItem
                                            key={role.value}
                                            value={role.value}
                                        >
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.roles} />
                        </div>
                    ) : null}

                    <div className="flex flex-col gap-1.5">
                        <Label>Effective date</Label>
                        <DatePicker
                            value={form.data.effective_date}
                            placeholder="Effective date"
                            onChange={(value) =>
                                form.setData('effective_date', value)
                            }
                        />
                        <InputError message={form.errors.effective_date} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="transfer-reason">Reason</Label>
                        <Textarea
                            id="transfer-reason"
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                            placeholder="Why is this person moving?"
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                </div>

                <SheetFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={form.processing}
                    >
                        {form.processing ? <Spinner /> : null}
                        Transfer
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
