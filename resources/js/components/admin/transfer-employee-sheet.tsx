import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { BranchOption, Option } from '@/components/admin/workforce-types';
import InputError from '@/components/input-error';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
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
import { cn } from '@/lib/utils';

export const TRANSFER_REASON_MAX = 500;

type FormAction = {
    action: string;
    method?: string;
};

type TransferEmployeeSheetBaseProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    action: FormAction;
    currentBranchId?: number | null;
    currentBranchName?: string | null;
};

type TransferStaffSheetProps = TransferEmployeeSheetBaseProps & {
    subjectType: 'branch_staff';
    branchOptionsUrl: string;
    roleOptionsUrl: string;
    currentRoles?: string[];
    branches?: never;
};

type TransferDriverSheetProps = TransferEmployeeSheetBaseProps & {
    subjectType: 'driver';
    branches: BranchOption[];
    branchOptionsUrl?: never;
    roleOptionsUrl?: never;
    currentRoles?: never;
};

export type TransferEmployeeSheetProps =
    | TransferStaffSheetProps
    | TransferDriverSheetProps;

export function TransferEmployeeSheet(props: TransferEmployeeSheetProps) {
    const {
        open,
        onOpenChange,
        action,
        subjectType,
        currentBranchId = null,
        currentBranchName = null,
    } = props;

    const isStaff = subjectType === 'branch_staff';
    const currentRoles = isStaff ? (props.currentRoles ?? []) : [];

    const form = useForm({
        to_branch_id: '',
        effective_date: '',
        reason: '',
        roles: [] as string[],
    });

    const [destinationLabel, setDestinationLabel] = useState<string | null>(
        null,
    );
    const [destinationRoles, setDestinationRoles] = useState<Option[]>([]);
    const [rolesLoading, setRolesLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        form.setData({
            to_branch_id: '',
            effective_date: '',
            reason: '',
            roles: [],
        });
        form.clearErrors();
        setDestinationLabel(null);
        setDestinationRoles([]);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, currentBranchId, currentRoles.join('|')]);

    useEffect(() => {
        if (!isStaff || !open || !form.data.to_branch_id) {
            return;
        }

        const roleOptionsUrl = props.roleOptionsUrl;
        const controller = new AbortController();

        setRolesLoading(true);

        const url = new URL(roleOptionsUrl, window.location.origin);
        url.searchParams.set('branch_id', form.data.to_branch_id);

        void fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Failed to load roles');
                }

                const payload = (await response.json()) as {
                    data?: Option[];
                };

                const options = payload.data ?? [];
                setDestinationRoles(options);

                const keep = currentRoles.filter((role) =>
                    options.some((option) => option.value === role),
                );

                form.setData('roles', keep.slice(0, 1));
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                setDestinationRoles([]);
                form.setData('roles', []);
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setRolesLoading(false);
                }
            });

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isStaff, open, form.data.to_branch_id]);

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
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-lg"
            >
                <SheetHeader className="space-y-1.5 border-b p-6 text-left">
                    <SheetTitle>
                        {isStaff ? 'Transfer staff' : 'Transfer driver'}
                    </SheetTitle>
                    <SheetDescription>
                        {isStaff
                            ? 'Move this person to another branch with a role at the destination. User type never changes.'
                            : 'Move this driver to another branch. Drivers keep the driver role.'}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-1 flex-col gap-6 overflow-y-auto p-6">
                    <div className="bg-muted/50 flex flex-col gap-1.5 rounded-lg border px-4 py-3">
                        <Label className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            Current branch
                        </Label>
                        <p className="text-sm font-medium">
                            {currentBranchName ?? '—'}
                        </p>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label>
                            Destination branch{' '}
                            <span className="text-primary">*</span>
                        </Label>
                        {isStaff ? (
                            <InfiniteScrollCombobox
                                optionsUrl={props.branchOptionsUrl}
                                value={form.data.to_branch_id || null}
                                onValueChange={(value, option) => {
                                    form.setData(
                                        'to_branch_id',
                                        value ?? '',
                                    );
                                    setDestinationLabel(option?.label ?? null);
                                    form.setData('roles', []);
                                    setDestinationRoles([]);
                                }}
                                selectedLabel={destinationLabel}
                                placeholder="Select branch"
                                searchPlaceholder="Search branches…"
                                emptyText="No branch found."
                                descriptionKey={null}
                                className="w-full"
                            />
                        ) : (
                            <Select
                                value={form.data.to_branch_id}
                                onValueChange={(value) =>
                                    form.setData('to_branch_id', value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    {props.branches.map((branch) => (
                                        <SelectItem
                                            key={branch.id}
                                            value={String(branch.id)}
                                        >
                                            {branch.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <InputError message={form.errors.to_branch_id} />
                    </div>

                    {isStaff ? (
                        <div className="flex flex-col gap-2">
                            <Label>
                                Role at destination{' '}
                                <span className="text-primary">*</span>
                            </Label>
                            <Select
                                value={form.data.roles[0] ?? ''}
                                onValueChange={(value) =>
                                    form.setData(
                                        'roles',
                                        value ? [value] : [],
                                    )
                                }
                                disabled={
                                    !form.data.to_branch_id || rolesLoading
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue
                                        placeholder={
                                            rolesLoading
                                                ? 'Loading roles…'
                                                : form.data.to_branch_id
                                                  ? 'Select role'
                                                  : 'Select a branch first'
                                        }
                                    />
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

                    <div className="flex flex-col gap-2">
                        <Label>
                            Effective date{' '}
                            <span className="text-primary">*</span>
                        </Label>
                        <DatePicker
                            value={form.data.effective_date}
                            placeholder="Effective date"
                            onChange={(value) =>
                                form.setData('effective_date', value)
                            }
                        />
                        <InputError message={form.errors.effective_date} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <div className="flex items-center justify-between gap-3">
                            <Label htmlFor="transfer-reason">
                                Reason <span className="text-primary">*</span>
                            </Label>
                            <p
                                id="transfer-reason-count"
                                className={cn(
                                    'text-xs tabular-nums',
                                    form.data.reason.length >= TRANSFER_REASON_MAX
                                        ? 'text-destructive'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {form.data.reason.length}/{TRANSFER_REASON_MAX}
                            </p>
                        </div>
                        <Textarea
                            id="transfer-reason"
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData(
                                    'reason',
                                    event.target.value.slice(
                                        0,
                                        TRANSFER_REASON_MAX,
                                    ),
                                )
                            }
                            maxLength={TRANSFER_REASON_MAX}
                            placeholder="Why is this person moving?"
                            aria-describedby="transfer-reason-count"
                            className="min-h-24"
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                </div>

                <SheetFooter className="border-t p-6 sm:flex-row sm:justify-end sm:gap-3">
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
