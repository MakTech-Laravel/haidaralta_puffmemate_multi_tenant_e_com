import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { Column } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type DataTableColumnHeaderProps<TData, TValue> = {
    column: Column<TData, TValue>;
    title: string;
    sortable?: boolean;
    sortKey?: string;
    currentSort?: string | null;
    onSort?: (column: string) => void;
    className?: string;
};

export function DataTableColumnHeader<TData, TValue>({
    title,
    sortable = false,
    sortKey,
    currentSort = null,
    onSort,
    className,
}: DataTableColumnHeaderProps<TData, TValue>) {
    if (!sortable || !sortKey || !onSort) {
        return <span className={cn(className)}>{title}</span>;
    }

    const isAsc = currentSort === sortKey;
    const isDesc = currentSort === `-${sortKey}`;

    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className={cn('-ml-3 h-8', className)}
            onClick={() => onSort(sortKey)}
        >
            <span>{title}</span>
            {isDesc ? (
                <ArrowDown className="size-3.5" />
            ) : isAsc ? (
                <ArrowUp className="size-3.5" />
            ) : (
                <ArrowUpDown className="size-3.5 opacity-50" />
            )}
        </Button>
    );
}
