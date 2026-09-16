import type { ReactNode } from 'react';
import {
    ALL_DUTY_DAYS,
    DutyDaysPicker,
} from '@/components/admin/duty-days-picker';
import InputError from '@/components/input-error';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TimePicker } from '@/components/ui/time-picker';

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string };

export type DriverRosterFieldsValue = {
    branch_id: string;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    days_of_week: number[];
    status: string;
};

type DriverRosterFieldsProps = {
    value: DriverRosterFieldsValue;
    onChange: (patch: Partial<DriverRosterFieldsValue>) => void;
    rosterStatuses: Option[];
    branches?: BranchOption[];
    showBranch?: boolean;
    includeHiddenInputs?: boolean;
    errors?: Record<string, string | undefined>;
    errorPrefix?: string;
    disabled?: boolean;
    statusTrailing?: ReactNode;
};

export function emptyRosterFields(defaults: {
    branchId?: string;
    status: string;
}): DriverRosterFieldsValue {
    return {
        branch_id: defaults.branchId ?? '',
        start_date: '',
        end_date: '',
        start_time: '',
        end_time: '',
        days_of_week: [...ALL_DUTY_DAYS],
        status: defaults.status,
    };
}

export function formatRosterPeriodLine(roster: {
    period_label?: string | null;
    start_time?: string | null;
    end_time?: string | null;
    days_label?: string | null;
}): string {
    const hours =
        roster.start_time && roster.end_time
            ? `${roster.start_time}–${roster.end_time}`
            : null;

    return [roster.period_label, hours, roster.days_label]
        .filter((part): part is string => Boolean(part))
        .join(' · ');
}

function fieldError(
    errors: Record<string, string | undefined> | undefined,
    name: string,
    prefix?: string,
): string | undefined {
    if (!errors) {
        return undefined;
    }

    if (prefix) {
        return errors[`${prefix}.${name}`];
    }

    return errors[name];
}

export function DriverRosterFields({
    value,
    onChange,
    rosterStatuses,
    branches = [],
    showBranch = false,
    includeHiddenInputs = false,
    errors,
    errorPrefix,
    disabled = false,
    statusTrailing,
}: DriverRosterFieldsProps) {
    const startDateError = fieldError(errors, 'start_date', errorPrefix);
    const endDateError = fieldError(errors, 'end_date', errorPrefix);
    const startTimeError = fieldError(errors, 'start_time', errorPrefix);
    const endTimeError = fieldError(errors, 'end_time', errorPrefix);
    const daysError = fieldError(errors, 'days_of_week', errorPrefix);
    const statusError = fieldError(errors, 'status', errorPrefix);
    const branchError = fieldError(errors, 'branch_id', errorPrefix);

    return (
        <div className="grid gap-3">
            {includeHiddenInputs ? (
                <>
                    {showBranch || value.branch_id ? (
                        <input
                            type="hidden"
                            name="branch_id"
                            value={value.branch_id}
                        />
                    ) : null}
                    <input
                        type="hidden"
                        name="start_date"
                        value={value.start_date}
                    />
                    <input
                        type="hidden"
                        name="end_date"
                        value={value.end_date}
                    />
                    <input
                        type="hidden"
                        name="start_time"
                        value={value.start_time}
                    />
                    <input
                        type="hidden"
                        name="end_time"
                        value={value.end_time}
                    />
                    <input type="hidden" name="status" value={value.status} />
                    {value.days_of_week.map((day) => (
                        <input
                            key={day}
                            type="hidden"
                            name="days_of_week[]"
                            value={day}
                        />
                    ))}
                </>
            ) : null}

            {showBranch ? (
                <div className="flex flex-col gap-1.5">
                    <Label>Branch</Label>
                    <Select
                        value={value.branch_id}
                        onValueChange={(branchId) =>
                            onChange({ branch_id: branchId })
                        }
                        disabled={disabled || branches.length === 0}
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
                    <InputError message={branchError} />
                </div>
            ) : null}

            <div className="grid min-w-0 gap-3 sm:grid-cols-2">
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Start date</Label>
                    <DatePicker
                        value={value.start_date}
                        placeholder="Start date"
                        max={value.end_date || null}
                        disabled={disabled}
                        onChange={(startDate) => {
                            onChange({
                                start_date: startDate,
                                end_date: value.end_date || startDate,
                            });
                        }}
                    />
                    <InputError message={startDateError} />
                </div>
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>End date</Label>
                    <DatePicker
                        value={value.end_date}
                        placeholder="End date"
                        min={value.start_date || null}
                        disabled={disabled}
                        onChange={(endDate) => {
                            onChange({
                                end_date: endDate,
                                start_date:
                                    value.start_date &&
                                    endDate &&
                                    value.start_date > endDate
                                        ? endDate
                                        : value.start_date,
                            });
                        }}
                    />
                    <InputError message={endDateError} />
                </div>
            </div>

            <div className="grid min-w-0 gap-3 sm:grid-cols-2">
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Start time</Label>
                    <TimePicker
                        value={value.start_time}
                        placeholder="Start time"
                        disabled={disabled}
                        onChange={(startTime) =>
                            onChange({ start_time: startTime })
                        }
                    />
                    <InputError message={startTimeError} />
                </div>
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>End time</Label>
                    <TimePicker
                        value={value.end_time}
                        placeholder="End time"
                        disabled={disabled}
                        onChange={(endTime) => onChange({ end_time: endTime })}
                    />
                    <InputError message={endTimeError} />
                </div>
            </div>

            <div className="flex flex-col gap-1.5">
                <Label>Days</Label>
                <DutyDaysPicker
                    value={value.days_of_week}
                    disabled={disabled}
                    onChange={(daysOfWeek) =>
                        onChange({ days_of_week: daysOfWeek })
                    }
                />
                <InputError message={daysError} />
            </div>

            <div className="flex items-end gap-2">
                <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                    <Label>Status</Label>
                    <Select
                        value={value.status}
                        onValueChange={(status) => onChange({ status })}
                        disabled={disabled}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            {rosterStatuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={statusError} />
                </div>
                {statusTrailing}
            </div>
        </div>
    );
}
