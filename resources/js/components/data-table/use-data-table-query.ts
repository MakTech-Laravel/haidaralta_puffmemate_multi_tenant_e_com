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

export function buildDataTableQuery(state: DataTableQueryState): QueryParams {
    const filter: Record<string, string> = {};

    Object.entries(state.filter).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '' || value === 'all') {
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
        ...(search !== null &&
        search !== undefined &&
        search !== ''
            ? { archives_search: String(search) }
            : {}),
        ...(state.sort ? { archives_sort: state.sort } : {}),
        archives_per_page: state.per_page,
        ...(state.page && state.page > 1
            ? { archives_page: state.page }
            : {}),
    };
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
    const skipNextSearchEffect = useRef(true);
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

            router.get(
                indexUrl,
                {
                    ...extra,
                    ...buildQueryRef.current(next),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        },
        [indexUrl],
    );

    useEffect(() => {
        if (skipNextSearchEffect.current) {
            skipNextSearchEffect.current = false;

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
