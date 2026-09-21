import {
    ALL_DUTY_DAYS,
    DutyDaysPicker,
} from '@/components/admin/duty-days-picker';
import type {
    AssignablePerson,
    BranchOption,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
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
import { Textarea } from '@/components/ui/textarea';

export type ShiftAssignmentFieldsValue = {
    user_id: string;
    branch_id: string;
    shift_template_id: string;
    shift_roster_period_id: string;
    start_date: string;
    end_date: string;
    days_of_week: number[];
    reason: string;
};

export function emptyShiftAssignmentFields(): ShiftAssignmentFieldsValue {
    return {
        user_id: '',
        branch_id: '',
        shift_template_id: '',
        shift_roster_period_id: '',
        start_date: '',
        end_date: '',
        days_of_week: [...ALL_DUTY_DAYS],
        reason: '',
    };
}

type ShiftAssignmentFieldsProps = {
    value: ShiftAssignmentFieldsValue;
    onChange: (patch: Partial<ShiftAssignmentFieldsValue>) => void;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    branches: BranchOption[];
    lockBranch?: boolean;
    errors?: Record<string, string | undefined>;
};

export function ShiftAssignmentFields({
    value,
    onChange,
    people,
    templates,
    periods,
    branches,
    lockBranch = false,
    errors = {},
}: ShiftAssignmentFieldsProps) {
    const selectedPeriod = periods.find(
        (period) => String(period.id) === value.shift_roster_period_id,
    );

    return (
        <div className="grid gap-4">
            <input type="hidden" name="user_id" value={value.user_id} />
            <input type="hidden" name="branch_id" value={value.branch_id} />
            <input
                type="hidden"
                name="shift_template_id"
                value={value.shift_template_id}
            />
            <input
                type="hidden"
                name="shift_roster_period_id"
                value={value.shift_roster_period_id}
            />
            <input type="hidden" name="start_date" value={value.start_date} />
            <input type="hidden" name="end_date" value={value.end_date} />
            {value.days_of_week.map((day) => (
                <input
                    key={day}
                    type="hidden"
                    name="days_of_week[]"
                    value={day}
                />
            ))}

            <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Person</Label>
                    <Select
                        value={value.user_id}
                        onValueChange={(userId) => {
                            const person = people.find(
                                (item) => String(item.id) === userId,
                            );

                            onChange({
                                user_id: userId,
                                branch_id:
                                    person?.user_type === 'branch' &&
                                    person.branch_id
                                        ? String(person.branch_id)
                                        : value.branch_id,
                            });
                        }}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select person" />
                        </SelectTrigger>
                        <SelectContent>
                            {people.map((person) => (
                                <SelectItem
                                    key={person.id}
                                    value={String(person.id)}
                                >
                                    {person.name} ·{' '}
                                    {person.user_type_label ?? person.user_type}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.user_id} />
                </div>

                {lockBranch ? (
                    <InputError message={errors.branch_id} />
                ) : (
                    <div className="flex min-w-0 flex-col gap-1.5">
                        <Label>Branch</Label>
                        <Select
                            value={value.branch_id}
                            onValueChange={(branchId) =>
                                onChange({ branch_id: branchId })
                            }
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
                        <InputError message={errors.branch_id} />
                    </div>
                )}
            </div>

            <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Shift</Label>
                    <Select
                        value={value.shift_template_id}
                        onValueChange={(shiftTemplateId) =>
                            onChange({ shift_template_id: shiftTemplateId })
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select shift" />
                        </SelectTrigger>
                        <SelectContent>
                            {templates.map((template) => (
                                <SelectItem
                                    key={template.id}
                                    value={String(template.id)}
                                >
                                    {template.name} · {template.start_time}–
                                    {template.end_time}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.shift_template_id} />
                </div>

                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Roster period</Label>
                    <Select
                        value={value.shift_roster_period_id}
                        onValueChange={(periodId) => {
                            const period = periods.find(
                                (item) => String(item.id) === periodId,
                            );

                            onChange({
                                shift_roster_period_id: periodId,
                                start_date:
                                    value.start_date ||
                                    period?.start_date ||
                                    '',
                                end_date:
                                    value.end_date || period?.end_date || '',
                            });
                        }}
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Select period" />
                        </SelectTrigger>
                        <SelectContent>
                            {periods.map((period) => (
                                <SelectItem
                                    key={period.id}
                                    value={String(period.id)}
                                >
                                    {period.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.shift_roster_period_id} />
                </div>
            </div>

            <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>Start date</Label>
                    <DatePicker
                        value={value.start_date}
                        placeholder="Start date"
                        min={selectedPeriod?.start_date}
                        max={value.end_date || selectedPeriod?.end_date}
                        onChange={(startDate) =>
                            onChange({
                                start_date: startDate,
                                end_date: value.end_date || startDate,
                            })
                        }
                    />
                    <InputError message={errors.start_date} />
                </div>
                <div className="flex min-w-0 flex-col gap-1.5">
                    <Label>End date</Label>
                    <DatePicker
                        value={value.end_date}
                        placeholder="End date"
                        min={value.start_date || selectedPeriod?.start_date}
                        max={selectedPeriod?.end_date}
                        onChange={(endDate) =>
                            onChange({
                                end_date: endDate,
                                start_date:
                                    value.start_date &&
                                    endDate &&
                                    value.start_date > endDate
                                        ? endDate
                                        : value.start_date,
                            })
                        }
                    />
                    <InputError message={errors.end_date} />
                </div>
            </div>

            <div className="flex flex-col gap-1.5">
                <Label>Days</Label>
                <DutyDaysPicker
                    value={value.days_of_week}
                    onChange={(daysOfWeek) => onChange({ days_of_week: daysOfWeek })}
                />
                <InputError
                    message={errors.days_of_week ?? errors['days_of_week.0']}
                />
            </div>

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="shift-reason">Reason</Label>
                <Textarea
                    id="shift-reason"
                    name="reason"
                    value={value.reason}
                    onChange={(event) =>
                        onChange({ reason: event.target.value })
                    }
                    placeholder="Optional note for this assignment"
                />
                <InputError message={errors.reason} />
            </div>
        </div>
    );
}
