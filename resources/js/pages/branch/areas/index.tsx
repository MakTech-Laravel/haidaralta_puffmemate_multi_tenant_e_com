import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import AreaController from '@/actions/App/Http/Controllers/Branch/AreaController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type AreaRow = {
    id: number;
    name: string;
    address: string;
    city: string;
    state: string;
    postal_code: string | null;
    is_active: boolean;
    created_at: string | null;
};

type Branch = { id: number; name: string; slug: string };

export default function BranchAreasIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<AreaRow>;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Areas',
                href: AreaController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: AreaController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<AreaRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Name"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <DataTableTruncated className="font-medium">
                        {row.original.name}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'address',
                header: 'Address',
                cell: ({ row }) => (
                    <DataTableTruncated>{row.original.address}</DataTableTruncated>
                ),
            },
            {
                accessorKey: 'city',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="City"
                        sortable
                        sortKey="city"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.city,
            },
            {
                accessorKey: 'state',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="State"
                        sortable
                        sortKey="state"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.state,
            },
            {
                accessorKey: 'is_active',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Status"
                        sortable
                        sortKey="is_active"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.is_active ? 'default' : 'secondary'
                        }
                    >
                        {row.original.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: AreaController.show.url([
                                    branch.slug,
                                    row.original.id,
                                ]),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: AreaController.edit.url([
                                    branch.slug,
                                    row.original.id,
                                ]),
                            },
                            {
                                label: 'Delete',
                                icon: Trash2,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () => setDeletingId(row.original.id),
                            },
                        ]}
                    />
                ),
            },
        ],
        [branch.slug, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Areas" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Areas"
                        description={`Delivery zones for ${branch.name}`}
                    />
                    <Button asChild>
                        <Link href={AreaController.create.url(branch.slug)}>
                            <Plus className="size-4" />
                            Add area
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No areas found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, address, city…"
                            onClear={query.clearFilters}
                            filters={
                                <div className="grid gap-2">
                                    <Label>Status</Label>
                                    <Select
                                        value={String(
                                            query.filters.is_active ?? 'all',
                                        )}
                                        onValueChange={(value) =>
                                            query.setFilter(
                                                'is_active',
                                                value === 'all' ? null : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-[10rem]">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All statuses
                                            </SelectItem>
                                            <SelectItem value="active">
                                                Active
                                            </SelectItem>
                                            <SelectItem value="inactive">
                                                Inactive
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            }
                        />
                    }
                />
            </div>

            <ConfirmDeleteDialog
                open={deletingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingId(null);
                    }
                }}
                description="This will permanently delete the delivery area."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        AreaController.destroy.url([branch.slug, deletingId]),
                        {
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingId(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}
