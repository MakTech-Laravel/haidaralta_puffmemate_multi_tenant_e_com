export type ActivityRow = {
    id: number;
    description: string;
    event: string | null;
    log_name: string | null;
    causer: { id: number; name: string; email: string | null } | null;
    subject: {
        type: string;
        id: number | string | null;
        label: string | null;
    } | null;
    created_at: string | null;
};

export type PaginatedActivities = {
    data: ActivityRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export type ActivityDetail = ActivityRow & {
    attribute_changes: {
        attributes: Record<string, unknown>;
        old: Record<string, unknown>;
    };
    properties: Record<string, unknown>;
};
