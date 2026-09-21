import { formatAppClock, formatAppDate } from '@/lib/datetime';

export type Option = { value: string; label: string };

export type BranchOption = {
    id: number;
    name: string;
    slug?: string;
};

export type ShiftTemplateOption = {
    id: number;
    name: string;
    owner_type: string;
    owner_type_label: string;
    branch_id: number | null;
    branch_name?: string | null;
    start_time: string | null;
    end_time: string | null;
    is_overnight: boolean;
    is_active: boolean;
};

export type ShiftPeriodOption = {
    id: number;
    name: string;
    owner_type: string;
    owner_type_label: string;
    branch_id: number | null;
    branch_name?: string | null;
    start_date: string | null;
    end_date: string | null;
    is_active: boolean;
};

export type AssignablePerson = {
    id: number;
    name: string;
    user_type: string;
    branch_id: number | null;
};

export type ShiftAssignmentRow = {
    id: number;
    user_id: number;
    user_name?: string | null;
    branch_id: number;
    branch_name?: string | null;
    shift_template_id: number;
    shift_template_name?: string | null;
    shift_roster_period_id: number;
    shift_roster_period_name?: string | null;
    start_date: string | null;
    end_date: string | null;
    start_time: string | null;
    end_time: string | null;
    is_overnight?: boolean;
    days_of_week: number[];
    days_label: string | null;
    status: string | null;
    status_label: string | null;
    covers_now?: boolean;
};

export type TransferRow = {
    id: number;
    user_id: number;
    user_name?: string | null;
    subject_type: string;
    subject_type_label: string;
    transfer_type: string;
    transfer_type_label: string;
    from_branch_id: number | null;
    from_branch_name?: string | null;
    to_branch_id: number | null;
    to_branch_name?: string | null;
    from_role: string | null;
    to_role: string | null;
    effective_date: string | null;
    reason: string | null;
    transferred_by_name?: string | null;
};

export function formatDutyLine(row: {
    start_date?: string | null;
    end_date?: string | null;
    start_time?: string | null;
    end_time?: string | null;
    days_label?: string | null;
    shift_template_name?: string | null;
}): string {
    const start = row.start_date ? formatAppDate(row.start_date) : '';
    const end = row.end_date ? formatAppDate(row.end_date) : '';
    const dates =
        start && end ? `${start} – ${end}` : start || end || null;
    const startTime = row.start_time ? formatAppClock(row.start_time) : '';
    const endTime = row.end_time ? formatAppClock(row.end_time) : '';
    const hours =
        startTime && endTime
            ? `${startTime}–${endTime}`
            : startTime || endTime || null;

    return [row.shift_template_name, dates, hours, row.days_label]
        .filter((part): part is string => Boolean(part))
        .join(' · ');
}
