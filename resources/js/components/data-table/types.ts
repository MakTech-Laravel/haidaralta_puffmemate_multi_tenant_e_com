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
    filters: Record<
        string,
        string | number | boolean | string[] | number[] | null | undefined
    >;
    filterOptions?: Record<string, unknown>;
    sort: string | null;
    per_page: number;
};

export type DataTableFilterValue =
    | string
    | number
    | boolean
    | string[]
    | number[]
    | null
    | undefined;

export type DataTableQueryState = {
    filter: Record<string, DataTableFilterValue>;
    sort: string | null;
    per_page: number;
    page?: number;
};

/** Build a single-page payload for in-memory TanStack lists (no Spatie URL). */
export function toClientDataTablePayload<T>(data: T[]): DataTablePayload<T> {
    const total = data.length;

    return {
        data,
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: Math.max(total, 1),
            total,
            from: total > 0 ? 1 : null,
            to: total > 0 ? total : null,
        },
        links: [],
        filters: {},
        sort: null,
        per_page: Math.max(total, 1),
    };
}
