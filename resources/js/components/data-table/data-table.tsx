import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type Column,
    type ColumnDef,
    type RowData,
    type VisibilityState,
} from '@tanstack/react-table';
import { type ReactNode, useEffect, useMemo, useState } from 'react';
import { DataTablePagination } from '@/components/data-table/data-table-pagination';
import { DataTableTruncated } from '@/components/data-table/data-table-truncated';
import { DataTableViewOptions } from '@/components/data-table/data-table-view-options';
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
        /** Label shown in the optional Columns menu. */
        label?: string;
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
    /**
     * Opt-in Columns menu. Omitted tables stay fully visible and show no menu.
     * The value is the localStorage key for this table's hidden columns.
     */
    columnVisibilityKey?: string;
};

function storageKey(key: string): string {
    return `datatable-columns:${key}`;
}

function readColumnVisibility(key: string): VisibilityState {
    if (typeof window === 'undefined') {
        return {};
    }

    try {
        const raw = window.localStorage.getItem(storageKey(key));

        if (raw === null) {
            return {};
        }

        const parsed: unknown = JSON.parse(raw);

        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            return {};
        }

        const visibility: VisibilityState = {};

        Object.entries(parsed).forEach(([id, visible]) => {
            if (typeof visible === 'boolean') {
                visibility[id] = visible;
            }
        });

        return visibility;
    } catch {
        return {};
    }
}

function writeColumnVisibility(key: string, visibility: VisibilityState): void {
    window.localStorage.setItem(storageKey(key), JSON.stringify(visibility));
}

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
    columnVisibilityKey,
}: DataTableProps<TData>) {
    const resolvedColumns = useMemo(
        () =>
            columns.map((column) => {
                const id =
                    column.id ??
                    ('accessorKey' in column
                        ? String(column.accessorKey)
                        : undefined);

                if (id !== 'actions') {
                    return column;
                }

                return { ...column, enableHiding: false };
            }),
        [columns],
    );
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        () =>
            columnVisibilityKey
                ? readColumnVisibility(columnVisibilityKey)
                : {},
    );
    const table = useReactTable({
        data: payload.data,
        columns: resolvedColumns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        pageCount: payload.meta.last_page,
        rowCount: payload.meta.total,
        ...(columnVisibilityKey
            ? {
                  state: { columnVisibility },
                  onColumnVisibilityChange: (updater) => {
                      setColumnVisibility((current) => {
                          const next =
                              typeof updater === 'function'
                                  ? updater(current)
                                  : updater;

                          writeColumnVisibility(columnVisibilityKey, next);

                          return next;
                      });
                  },
              }
            : {}),
    });
    const visibleColumnCount = table.getVisibleLeafColumns().length;

    useEffect(() => {
        if (!columnVisibilityKey) {
            return;
        }

        const hideable = table
            .getAllColumns()
            .filter((column) => column.getCanHide());

        if (
            hideable.length > 0 &&
            hideable.every((column) => !column.getIsVisible())
        ) {
            setColumnVisibility({});
            writeColumnVisibility(columnVisibilityKey, {});
        }
    }, [columnVisibility, columnVisibilityKey, table]);

    return (
        <div className="space-y-4">
            {columnVisibilityKey ? (
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="min-w-0 flex-1">{toolbar}</div>
                    <DataTableViewOptions table={table} />
                </div>
            ) : (
                toolbar
            )}
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
                                    colSpan={visibleColumnCount}
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
