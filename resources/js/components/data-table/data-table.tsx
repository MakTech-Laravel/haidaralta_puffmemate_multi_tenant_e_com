import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type Column,
    type ColumnDef,
    type RowData,
} from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableTruncated } from '@/components/data-table/data-table-truncated';
import type { DataTablePayload } from '@/components/data-table/types';
import { cn } from '@/lib/utils';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

declare module '@tanstack/react-table' {
    interface ColumnMeta<TData extends RowData, TValue> {
        truncate?: boolean;
    }
}

type DataTableProps<TData> = {
    columns: ColumnDef<TData, unknown>[];
    payload: DataTablePayload<TData>;
    toolbar?: ReactNode;
    perPage: number;
    onPerPageChange: (value: number) => void;
    emptyMessage?: string;
    /** Hide footer when rendering an in-memory / nested page list. */
    showPagination?: boolean;
    /** Background list reload; dims rows without a full-page overlay. */
    isLoading?: boolean;
};

function isActionsColumn(columnId: string): boolean {
    return columnId === 'actions';
}

function isImageColumn(columnId: string): boolean {
    return columnId === 'image';
}

function cellClassName<TData>(
    column: Column<TData, unknown>,
): string | undefined {
    if (isActionsColumn(column.id)) {
        return 'w-[1%] text-right';
    }

    if (isImageColumn(column.id)) {
        return undefined;
    }

    return 'max-w-[14rem] min-w-0 overflow-hidden';
}

function shouldTruncateCell<TData>(column: Column<TData, unknown>): boolean {
    if (isActionsColumn(column.id) || isImageColumn(column.id)) {
        return false;
    }

    return column.columnDef.meta?.truncate !== false;
}

export function DataTable<TData>({
    columns,
    payload,
    toolbar,
    perPage,
    onPerPageChange,
    emptyMessage = 'No results found.',
    showPagination = true,
    isLoading = false,
}: DataTableProps<TData>) {
    const table = useReactTable({
        data: payload.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        pageCount: payload.meta.last_page,
        rowCount: payload.meta.total,
    });

    return (
        <div className="space-y-4">
            {toolbar}
            <div
                className={cn(
                    'overflow-hidden rounded-xl border transition-opacity',
                    isLoading && 'pointer-events-none opacity-60',
                )}
                aria-busy={isLoading || undefined}
            >
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        className={
                                            isActionsColumn(header.column.id)
                                                ? 'w-[1%] text-right'
                                                : undefined
                                        }
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.length > 0 ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getVisibleCells().map((cell) => {
                                        const rendered = flexRender(
                                            cell.column.columnDef.cell,
                                            cell.getContext(),
                                        );

                                        return (
                                            <TableCell
                                                key={cell.id}
                                                className={cellClassName(
                                                    cell.column,
                                                )}
                                            >
                                                {shouldTruncateCell(
                                                    cell.column,
                                                ) ? (
                                                    <DataTableTruncated>
                                                        {rendered}
                                                    </DataTableTruncated>
                                                ) : (
                                                    rendered
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="text-muted-foreground h-24 text-center"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            {showPagination ? (
                <DataTablePagination
                    meta={payload.meta}
                    links={payload.links}
                    perPage={perPage}
                    onPerPageChange={onPerPageChange}
                />
            ) : null}
        </div>
    );
}
