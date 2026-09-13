import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type ColumnDef,
} from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import { DataTableToolbar } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type PermissionGroupItem = {
    value: string;
    label: string;
    roles_count: number;
};

export type PermissionGroup = {
    group: string;
    permissions: PermissionGroupItem[];
};

const permissionColumns: ColumnDef<PermissionGroupItem>[] = [
    {
        accessorKey: 'label',
        header: 'Permission',
    },
    {
        accessorKey: 'value',
        header: 'Key',
        cell: ({ row }) => (
            <Badge variant="outline">{row.original.value}</Badge>
        ),
    },
    {
        accessorKey: 'roles_count',
        header: 'Roles',
    },
];

function PermissionGroupRows({
    permissions,
}: {
    permissions: PermissionGroupItem[];
}) {
    const table = useReactTable({
        data: permissions,
        columns: permissionColumns,
        getCoreRowModel: getCoreRowModel(),
    });

    return (
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
                                              header.column.columnDef.header,
                                              header.getContext(),
                                          )}
                                </TableHead>
                            ))}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.map((row) => (
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
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Grouped permission catalog UI (client search). Intentional exception to
 * Eloquent *DataTable — Spatie permission keys are a static seeded catalog.
 */
export function PermissionGroupsTable({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    const [search, setSearch] = useState('');

    const filteredGroups = useMemo(() => {
        const q = search.trim().toLowerCase();

        if (q === '') {
            return permissionGroups;
        }

        return permissionGroups
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter(
                    (permission) =>
                        permission.label.toLowerCase().includes(q) ||
                        permission.value.toLowerCase().includes(q) ||
                        group.group.toLowerCase().includes(q),
                ),
            }))
            .filter((group) => group.permissions.length > 0);
    }, [permissionGroups, search]);

    return (
        <div className="space-y-6">
            <DataTableToolbar
                search={search}
                onSearchChange={setSearch}
                searchPlaceholder="Search permissions…"
                onClear={() => setSearch('')}
            />

            {filteredGroups.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    {search.trim() === ''
                        ? 'No permissions available.'
                        : `No permissions match “${search}”.`}
                </p>
            ) : (
                filteredGroups.map((group) => (
                    <section key={group.group} className="space-y-3">
                        <h2 className="text-sm font-medium">{group.group}</h2>
                        <PermissionGroupRows permissions={group.permissions} />
                    </section>
                ))
            )}
        </div>
    );
}
