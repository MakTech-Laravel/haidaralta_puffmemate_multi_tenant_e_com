import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type {
    DataTableFilterValue,
    DataTableQueryState,
} from '@/components/data-table/types';
import type { QueryParams } from '@/wayfinder';

type UseDataTableQueryOptions = {
    indexUrl: string;
    initial: DataTableQueryState;
    searchKey?: string;
    debounceMs?: number;
    /** Merge sibling-table (or other) query keys so dual lists on one page keep state. */
    extraQuery?: QueryParams | (() => QueryParams);
    /** Override default Spatie-style query serialization. */
    buildQuery?: (state: DataTableQueryState) => QueryParams;
};

function isOmittedFilterValue(
    key: string,
    value: DataTableFilterValue,
): boolean {
    if (
        value === null ||
        value === undefined ||
        value === '' ||
        value === 'all'
    ) {
        return true;
    }

    return key === 'trashed' && value === 'active';
}

export function buildDataTableQuery(state: DataTableQueryState): QueryParams {
    const filter: QueryParams = {};

    Object.entries(state.filter).forEach(([key, value]) => {
        if (isOmittedFilterValue(key, value)) {
            return;
        }

        if (Array.isArray(value)) {
            if (value.length === 0) {
                return;
            }

            filter[key] = value.map(String);

            return;
        }

        filter[key] = String(value);
    });

    return {
        ...(Object.keys(filter).length > 0 ? { filter } : {}),
        ...(state.sort ? { sort: state.sort } : {}),
        per_page: state.per_page,
        ...(state.page && state.page > 1 ? { page: state.page } : {}),
    };
}

/** Query keys for filesystem backup zip list (coexists with history DataTable). */
export function buildArchivesDataTableQuery(
    state: DataTableQueryState,
): QueryParams {
    const search = state.filter.search;

    return {
        ...(search !== null && search !== undefined && search !== ''
            ? { archives_search: String(search) }
            : {}),
        ...(state.sort ? { archives_sort: state.sort } : {}),
        archives_per_page: state.per_page,
        ...(state.page && state.page > 1 ? { archives_page: state.page } : {}),
    };
}

function appendQueryParam(
    params: URLSearchParams,
    prefix: string,
    value: QueryParams[string],
): void {
    if (value === undefined) {
        return;
    }

    if (value === null) {
        params.append(prefix, '');

        return;
    }

    if (Array.isArray(value)) {
        value.forEach((item) => {
            params.append(`${prefix}[]`, String(item));
        });

        return;
    }

    if (typeof value === 'object') {
        Object.entries(value).forEach(([key, nested]) => {
            appendQueryParam(
                params,
                prefix === '' ? key : `${prefix}[${key}]`,
                nested,
            );
        });

        return;
    }

    params.append(prefix, String(value));
}

export function queryToSearchParams(query: QueryParams): URLSearchParams {
    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        appendQueryParam(params, key, value);
    });

    return params;
}

function sameSearchParams(
    left: URLSearchParams,
    right: URLSearchParams,
): boolean {
    const leftKeys = [...new Set(left.keys())].sort();
    const rightKeys = [...new Set(right.keys())].sort();

    if (
        leftKeys.length !== rightKeys.length ||
        leftKeys.some((key, index) => key !== rightKeys[index])
    ) {
        return false;
    }

    return leftKeys.every((key) => {
        const leftValues = left.getAll(key).sort();
        const rightValues = right.getAll(key).sort();

        return (
            leftValues.length === rightValues.length &&
            leftValues.every((value, index) => value === rightValues[index])
        );
    });
}

function normalizePathname(pathname: string): string {
    if (pathname === '/') {
        return pathname;
    }

    return pathname.replace(/\/+$/, '');
}

function queryMatchesCurrentUrl(indexUrl: string, query: QueryParams): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    const current = new URL(window.location.href);
    const next = new URL(indexUrl, window.location.origin);

    if (
        normalizePathname(current.pathname) !== normalizePathname(next.pathname)
    ) {
        return false;
    }

    return sameSearchParams(current.searchParams, queryToSearchParams(query));
}

export function useDataTableQuery({
    indexUrl,
    initial,
    searchKey = 'search',
    debounceMs = 300,
    extraQuery,
    buildQuery = buildDataTableQuery,
}: UseDataTableQueryOptions) {
    const [search, setSearch] = useState(
        String(initial.filter[searchKey] ?? ''),
    );
    const [filters, setFilters] = useState(initial.filter);
    const [sort, setSort] = useState<string | null>(initial.sort);
    const [perPage, setPerPage] = useState(initial.per_page);
    const committedSearch = useRef(String(initial.filter[searchKey] ?? ''));
    const extraQueryRef = useRef(extraQuery);
    const buildQueryRef = useRef(buildQuery);

    extraQueryRef.current = extraQuery;
    buildQueryRef.current = buildQuery;

    const visit = useCallback(
        (next: DataTableQueryState) => {
            const extra =
                typeof extraQueryRef.current === 'function'
                    ? extraQueryRef.current()
                    : (extraQueryRef.current ?? {});
            const query = {
                ...extra,
                ...buildQueryRef.current(next),
            };

            committedSearch.current = String(next.filter[searchKey] ?? '');

            if (queryMatchesCurrentUrl(indexUrl, query)) {
                return;
            }

            router.get(indexUrl, query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        [indexUrl, searchKey],
    );

    useEffect(() => {
        if (search === committedSearch.current) {
            return;
        }

        const timer = window.setTimeout(() => {
            const nextFilter = {
                ...filters,
                [searchKey]: search.trim() === '' ? null : search.trim(),
            };
            setFilters(nextFilter);
            visit({
                filter: nextFilter,
                sort,
                per_page: perPage,
                page: 1,
            });
        }, debounceMs);

        return () => window.clearTimeout(timer);
        // Intentionally only react to search typing — filter/sort changes visit immediately.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, debounceMs, searchKey]);

    const setFilter = (key: string, value: DataTableFilterValue) => {
        const nextFilter = { ...filters, [key]: value };
        setFilters(nextFilter);
        visit({
            filter: nextFilter,
            sort,
            per_page: perPage,
            page: 1,
        });
    };

    const toggleSort = (column: string) => {
        let nextSort: string | null = column;

        if (sort === column) {
            nextSort = `-${column}`;
        } else if (sort === `-${column}`) {
            nextSort = column;
        }

        setSort(nextSort);
        visit({
            filter: filters,
            sort: nextSort,
            per_page: perPage,
            page: 1,
        });
    };

    const changePerPage = (value: number) => {
        setPerPage(value);
        visit({
            filter: filters,
            sort,
            per_page: value,
            page: 1,
        });
    };

    const clearFilters = () => {
        const cleared = Object.fromEntries(
            Object.keys(filters).map((key) => [key, null]),
        ) as Record<string, DataTableFilterValue>;
        setFilters(cleared);
        setSearch('');
        setSort(null);
        visit({
            filter: cleared,
            sort: null,
            per_page: perPage,
            page: 1,
        });
    };

    return {
        search,
        setSearch,
        filters,
        setFilter,
        sort,
        toggleSort,
        perPage,
        changePerPage,
        clearFilters,
        visit,
    };
}
