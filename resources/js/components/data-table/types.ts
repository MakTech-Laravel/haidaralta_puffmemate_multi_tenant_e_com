export type DataTableLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type DataTableMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type DataTablePayload<T> = {
    data: T[];
    meta: DataTableMeta;
    links: DataTableLink[];
    filters: Record<string, string | number | boolean | null | undefined>;
    filterOptions?: Record<string, unknown>;
    sort: string | null;
    per_page: number;
};

export type DataTableFilterValue = string | number | boolean | null | undefined;

export type DataTableQueryState = {
    filter: Record<string, DataTableFilterValue>;
    sort: string | null;
    per_page: number;
    page?: number;
};
