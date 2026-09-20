import {
    buildDataTableQuery,
    queryToSearchParams,
    type DataTableQueryState,
} from '@/components/data-table';
import type { QueryParams } from '@/wayfinder';

export type ExportFormat = 'csv' | 'xlsx';

export type ExportScope = 'all' | 'period' | 'filters' | 'page';

export function buildExportHref(
    url: string,
    format: ExportFormat,
    scope: ExportScope,
    state: DataTableQueryState,
    extra: QueryParams = {},
): string {
    const params = queryToSearchParams({
        format,
        scope,
        ...extra,
        ...buildDataTableQuery(state),
    });
    const query = params.toString();

    return query === '' ? url : `${url}?${query}`;
}
