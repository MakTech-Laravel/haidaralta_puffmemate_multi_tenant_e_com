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
}: {
    search: string;
    onSearchChange: (value: string) => void;
    searchPlaceholder?: string;
    filters?: ReactNode;
    onClear?: () => void;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div className="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
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
                {filters}
                {onClear ? (
                    <Button type="button" variant="ghost" onClick={onClear}>
                        Clear
                    </Button>
                ) : null}
            </div>
            {actions}
        </div>
    );
}
