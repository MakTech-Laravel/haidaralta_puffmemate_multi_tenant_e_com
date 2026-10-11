import type { Table } from '@tanstack/react-table';
import { Columns3 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function DataTableViewOptions<TData>({
    table,
}: {
    table: Table<TData>;
}) {
    const hideable = table
        .getAllColumns()
        .filter((column) => column.getCanHide());
    const visibleHideable = hideable.filter((column) => column.getIsVisible());

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="mb-0.5 shrink-0"
                >
                    <Columns3 />
                    Columns
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-44">
                <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
                {hideable.map((column) => {
                    const label = column.columnDef.meta?.label ?? column.id;
                    const isLastVisible =
                        column.getIsVisible() && visibleHideable.length <= 1;

                    return (
                        <DropdownMenuCheckboxItem
                            key={column.id}
                            checked={column.getIsVisible()}
                            disabled={isLastVisible}
                            onCheckedChange={(value) =>
                                column.toggleVisibility(value === true)
                            }
                            onSelect={(event) => event.preventDefault()}
                        >
                            {label}
                        </DropdownMenuCheckboxItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
