import { formatAppClock, formatAppDate } from '@/lib/datetime';

export type Option = { value: string; label: string };

export type BranchOption = {
    id: number;
    name: string;
    slug?: string;
};

export type ShiftAudience = 'all' | 'staff' | 'driver';

export const SHIFT_AUDIENCE_OPTIONS: {
    value: ShiftAudience;
    label: string;
}[] = [
    { value: 'all', label: 'All' },
    { value: 'staff', label: 'Staff only' },
    { value: 'driver', label: 'Drivers only' },
];

export type ShiftTemplateOption = {
    id: number;
    name: string;
    owner_type: string;
    owner_type_label: string;
    branch_id: number | null;
    branch_name?: string | null;
    audience?: ShiftAudience;
    audience_label?: string | null;
    start_time: string | null;
    end_time: string | null;
    is_active: boolean;
    scheduled_count?: number;
};

export type AssignablePerson = {
    id: number;
    name: string;
    user_type: string;
    user_type_label?: string;
    branch_id: number | null;
    avatar_url?: string | null;
};

export type ShiftAssignmentPeopleKind = 'staff' | 'driver';

export type ShiftAssignmentWorkspaceQuery = {
    branch_id?: number;
    people_kind?: ShiftAssignmentPeopleKind;
    user_id?: number;
    month?: string;
    assignment_page?: number;
};

export type ShiftAssignmentChangePayload = {
    branch_id?: number;
    user_id: number;
    shift_template_id: number | null;
    assign_dates: string[];
    replace_day_ids: number[];
    remove_day_ids: number[];
    month?: string;
    assignment_page?: number;
};

export type ShiftAssignmentPeopleOption = {
    id: number;
    name: string;
    user_type: string;
    avatar_url: string | null;
};

export type ShiftAssignmentCalendarDay = {
    id: number;
    assignment_id: number;
    shift_template_id: number;
    name: string | null;
    start_time: string | null;
    end_time: string | null;
    editable: boolean;
};

export type ShiftAssignmentCalendarPayload = {
    month: string;
    today: string;
    grid_start: string;
    grid_end: string;
    days: Record<string, ShiftAssignmentCalendarDay[]>;
};

export type ShiftAssignmentGroup = {
    key: string;
    shift_template_id: number;
    name: string;
    start_time: string;
    end_time: string;
    start_date: string;
    end_date: string;
    date_count: number;
    editable: boolean;
    is_past: boolean;
};

export type ShiftAssignmentGroupsPayload = {
    data: ShiftAssignmentGroup[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export type ShiftAssignmentWorkspaceProps = {
    selectedBranch: BranchOption | null;
    selectedPeopleKind: ShiftAssignmentPeopleKind | null;
    selectedPerson: ShiftAssignmentPeopleOption | null;
    templates: ShiftTemplateOption[];
    calendar: ShiftAssignmentCalendarPayload | null;
    assignmentGroups: ShiftAssignmentGroupsPayload;
};

export type ShiftAssignmentPeopleOptionsPayload = {
    data: ShiftAssignmentPeopleOption[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export type ShiftWorkspaceStats = {
    on_duty: number;
    templates: number;
    active_templates: number;
};

export type AssignedShiftPersonRow = {
    id: number;
    user_id: number;
    user_name?: string | null;
    user_type?: string | null;
    avatar_url?: string | null;
    branch_id: number;
    branch_name?: string | null;
    shift_template_id: number;
    shift_template_name?: string | null;
    start_date: string | null;
    end_date: string | null;
    start_time: string | null;
    end_time: string | null;
};

export type ShiftDayKind = 'scheduled' | 'override' | 'weekly_off' | 'leave';

export type ShiftDutyDay = {
    id?: number;
    assignment_id?: number;
    user_id?: number;
    branch_id?: number;
    date: string;
    kind: ShiftDayKind;
    kind_label?: string;
    shift_template_id: number | null;
    shift_template_name?: string | null;
    start_time?: string | null;
    end_time?: string | null;
};

export type ShiftDayException = {
    date: string;
    kind: ShiftDayKind;
    shift_template_id: number | null;
};

export type ShiftAssignmentRow = {
    id: number;
    user_id: number;
    user_name?: string | null;
    user_type?: string | null;
    user_type_label?: string | null;
    role_label?: string | null;
    avatar_url?: string | null;
    branch_id: number;
    branch_name?: string | null;
    shift_template_id: number;
    shift_template_name?: string | null;
    start_date: string | null;
    end_date: string | null;
    start_time: string | null;
    end_time: string | null;
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
    const dates = start && end ? `${start} – ${end}` : start || end || null;
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

export function templateMatchesAudience(
    template: Pick<ShiftTemplateOption, 'audience'>,
    filter: ShiftAudience,
): boolean {
    if (filter === 'all') {
        return true;
    }

    const audience = template.audience ?? 'all';

    return audience === 'all' || audience === filter;
}

export function assignmentMatchesAudience(
    row: Pick<ShiftAssignmentRow, 'user_type' | 'shift_template_id'>,
    filter: ShiftAudience,
    templates: ShiftTemplateOption[],
): boolean {
    if (filter === 'all') {
        return true;
    }

    const template = templates.find(
        (item) => item.id === row.shift_template_id,
    );

    if (template === undefined || !templateMatchesAudience(template, filter)) {
        return false;
    }

    if (filter === 'driver') {
        return row.user_type === 'driver';
    }

    return row.user_type !== 'driver';
}
