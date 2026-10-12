import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export function DataTableToolbar({
    search,
    onSearchChange,
    searchPlaceholder = 'Search…',
    filters,
    onClear,
    actions,
    layout = 'inline',
}: {
    search: string;
    onSearchChange: (value: string) => void;
    searchPlaceholder?: string;
    filters?: ReactNode;
    onClear?: () => void;
    actions?: ReactNode;
    /** Stack search above filters when a page has several controls. */
    layout?: 'inline' | 'stacked';
}) {
    const searchField = (
        <div className="grid min-w-0 flex-1 gap-2">
            <label className="text-sm font-medium" htmlFor="data-table-search">
                Search
            </label>
            <Input
                id="data-table-search"
                value={search}
                onChange={(event) => onSearchChange(event.target.value)}
                placeholder={searchPlaceholder}
            />
        </div>
    );
    const clearButton = onClear ? (
        <Button type="button" variant="ghost" onClick={onClear}>
            Clear
        </Button>
    ) : null;

    if (layout === 'stacked') {
        return (
            <div className="flex flex-col gap-3">
                {searchField}
                <div className="flex flex-wrap items-end gap-2">
                    {filters}
                    {clearButton}
                </div>
                {actions}
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                {searchField}
                {filters}
                {clearButton}
            </div>
            {actions}
        </div>
    );
}
