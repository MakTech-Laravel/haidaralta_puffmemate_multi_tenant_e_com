import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import { ShiftPeriodSpan } from '@/components/admin/shift-period-span';
import type {
    BranchOption,
    Option,
    ShiftPeriodOption,
} from '@/components/admin/workforce-types';
import InputError from '@/components/input-error';
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
import { formatDayRange } from '@/lib/shift-format';
import { cn } from '@/lib/utils';

type FormAction = {
    action: string;
    method?: string;
};

type FormMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

type ShiftPeriodFormProps = {
    action: FormAction;
    ownerTypes?: Option[];
    branches?: BranchOption[];
    lockOwner?: boolean;
    showBranchSelect?: boolean;
    defaults?: Partial<ShiftPeriodOption> & { owner_type?: string };
    cancelHref: string;
    submitLabel?: string;
};

function sectionClassName(): string {
    return cn(
        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
        'sm:p-6',
    );
}

export function ShiftPeriodForm({
    action,
    ownerTypes = [],
    branches = [],
    lockOwner = false,
    showBranchSelect = false,
    defaults,
    cancelHref,
    submitLabel,
}: ShiftPeriodFormProps) {
    const [ownerType, setOwnerType] = useState(
        defaults?.owner_type ?? 'platform',
    );
    const [branchId, setBranchId] = useState(
        defaults?.branch_id ? String(defaults.branch_id) : '',
    );
    const [startDate, setStartDate] = useState(defaults?.start_date ?? '');
    const [endDate, setEndDate] = useState(defaults?.end_date ?? '');
    const [isActive, setIsActive] = useState(defaults?.is_active ?? true);

    return (
        <Form
            action={action.action}
            method={(action.method ?? 'post').toLowerCase() as FormMethod}
            className="w-full space-y-6"
            options={{ preserveScroll: true }}
            validationTimeout={500}
        >
            {({ processing, errors, validate }) => (
                <>
                    <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
                    <section className={sectionClassName()}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Roster period
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Named date range that assignments must fall
                                inside. This does not generate daily rows.
                            </p>
                        </div>

                        <div className="grid gap-4">
                            <div className="flex max-w-md flex-col gap-1.5">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={defaults?.name ?? ''}
                                    required
                                    placeholder="September 2026"
                                    autoComplete="off"
                                    aria-invalid={Boolean(errors.name)}
                                    onBlur={() => validate('name')}
                                />
                                <InputError message={errors.name} />
                            </div>

                            {lockOwner ? (
                                <>
                                    <input
                                        type="hidden"
                                        name="owner_type"
                                        value={ownerType}
                                    />
                                    {showBranchSelect ? (
                                        <div className="flex min-w-0 max-w-md flex-col gap-1.5">
                                            <Label>Branch</Label>
                                            <Select
                                                value={branchId}
                                                onValueChange={setBranchId}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select branch" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {branches.map((branch) => (
                                                        <SelectItem
                                                            key={branch.id}
                                                            value={String(
                                                                branch.id,
                                                            )}
                                                        >
                                                            {branch.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <input
                                                type="hidden"
                                                name="branch_id"
                                                value={branchId}
                                            />
                                            <InputError
                                                message={errors.branch_id}
                                            />
                                        </div>
                                    ) : branchId ? (
                                        <input
                                            type="hidden"
                                            name="branch_id"
                                            value={branchId}
                                        />
                                    ) : null}
                                </>
                            ) : (
                                <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                                    <div className="flex min-w-0 flex-col gap-1.5">
                                        <Label>Owner</Label>
                                        <Select
                                            value={ownerType}
                                            onValueChange={(value) => {
                                                setOwnerType(value);
                                                if (value === 'platform') {
                                                    setBranchId('');
                                                }
                                            }}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select owner" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ownerTypes.map((type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="owner_type"
                                            value={ownerType}
                                        />
                                        <InputError
                                            message={errors.owner_type}
                                        />
                                    </div>
                                    {ownerType === 'branch' ? (
                                        <div className="flex min-w-0 flex-col gap-1.5">
                                            <Label>Branch</Label>
                                            <Select
                                                value={branchId}
                                                onValueChange={setBranchId}
                                            >
                                                <SelectTrigger className="w-full">
                                                    <SelectValue placeholder="Select branch" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {branches.map((branch) => (
                                                        <SelectItem
                                                            key={branch.id}
                                                            value={String(
                                                                branch.id,
                                                            )}
                                                        >
                                                            {branch.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <input
                                                type="hidden"
                                                name="branch_id"
                                                value={branchId}
                                            />
                                            <InputError
                                                message={errors.branch_id}
                                            />
                                        </div>
                                    ) : null}
                                </div>
                            )}

                            <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                                <div className="flex min-w-0 flex-col gap-1.5">
                                    <Label>Start date</Label>
                                    <DatePicker
                                        value={startDate}
                                        placeholder="Start date"
                                        max={endDate || null}
                                        onChange={(value) => {
                                            setStartDate(value);
                                            if (!endDate) {
                                                setEndDate(value);
                                            }
                                        }}
                                    />
                                    <input
                                        type="hidden"
                                        name="start_date"
                                        value={startDate}
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="flex min-w-0 flex-col gap-1.5">
                                    <Label>End date</Label>
                                    <DatePicker
                                        value={endDate}
                                        placeholder="End date"
                                        min={startDate || null}
                                        onChange={setEndDate}
                                    />
                                    <input
                                        type="hidden"
                                        name="end_date"
                                        value={endDate}
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
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
                                    onCheckedChange={(value) =>
                                        setIsActive(value === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_active"
                                    className="font-normal"
                                >
                                    Active
                                </Label>
                                <InputError message={errors.is_active} />
                            </div>
                        </div>
                    </section>

                    <aside className="bg-card lg:sticky lg:top-6 space-y-4 rounded-2xl border p-5">
                        <div>
                            <p className="text-muted-foreground text-[11px] font-medium tracking-[0.16em] uppercase">
                                Preview
                            </p>
                            <p className="mt-1 text-sm font-medium">
                                {formatDayRange(startDate, endDate)}
                            </p>
                        </div>
                        <ShiftPeriodSpan
                            startDate={startDate}
                            endDate={endDate}
                            containsToday={
                                Boolean(startDate) &&
                                Boolean(endDate) &&
                                startDate <= todayIso() &&
                                endDate >= todayIso()
                            }
                            daysElapsed={
                                startDate
                                    ? daysBetween(startDate, todayIso()) + 1
                                    : 0
                            }
                            daysTotal={
                                startDate && endDate
                                    ? daysBetween(startDate, endDate) + 1
                                    : 0
                            }
                        />
                    </aside>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            {submitLabel ?? 'Save period'}
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

function todayIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function daysBetween(start: string, end: string): number {
    const from = Date.parse(`${start}T00:00:00`);
    const to = Date.parse(`${end}T00:00:00`);

    if (Number.isNaN(from) || Number.isNaN(to) || to < from) {
        return 0;
    }

    return Math.round((to - from) / 86_400_000);
}
