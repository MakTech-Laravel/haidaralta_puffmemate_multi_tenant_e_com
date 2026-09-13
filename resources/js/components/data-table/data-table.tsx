import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type ColumnDef,
} from '@tanstack/react-table';
import type { ReactNode } from 'react';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import type { DataTablePayload } from '@/components/data-table/types';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type DataTableProps<TData> = {
    columns: ColumnDef<TData, unknown>[];
    payload: DataTablePayload<TData>;
    toolbar?: ReactNode;
    perPage: number;
    onPerPageChange: (value: number) => void;
    emptyMessage?: string;
    /** Hide footer when rendering an in-memory / nested page list. */
    showPagination?: boolean;
};

export function DataTable<TData>({
    columns,
    payload,
    toolbar,
    perPage,
    onPerPageChange,
    emptyMessage = 'No results found.',
    showPagination = true,
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
            <div className="overflow-hidden rounded-xl border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
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
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
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
