import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchAreasController from '@/actions/App/Http/Controllers/Platform/BranchAreasController';
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
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
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
    branch: { id: number; name: string; slug: string } | null;
};

type SelectedBranch = { id: number; name: string; slug: string };

export default function PlatformBranchAreasIndex({
    table: tablePayload,
    canManage,
}: {
    table: DataTablePayload<AreaRow> & {
        filterOptions?: { selected_branch?: SelectedBranch | null };
    };
    canManage: boolean;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: BranchAreasController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const selectedBranch = tablePayload.filterOptions?.selected_branch;
    const branchFilter = String(query.filters.branch_id ?? 'all');

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
                id: 'branch',
                header: 'Branch',
                cell: ({ row }) => row.original.branch?.name ?? '—',
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
                                href: BranchAreasController.show.url(
                                    row.original.id,
                                ),
                            },
                            ...(canManage
                                ? [
                                      {
                                          label: 'Edit',
                                          icon: Pencil,
                                          href: BranchAreasController.edit.url(
                                              row.original.id,
                                          ),
                                      },
                                      {
                                          label: 'Delete',
                                          icon: Trash2,
                                          destructive: true,
                                          separatorBefore: true,
                                          onSelect: () =>
                                              setDeletingId(row.original.id),
                                      },
                                  ]
                                : []),
                        ]}
                    />
                ),
            },
        ],
        [canManage, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Areas" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Areas"
                        description="Delivery zones across all branches"
                    />
                    {canManage ? (
                        <Button asChild>
                            <Link href={BranchAreasController.create.url()}>
                                <Plus className="size-4" />
                                Add area
                            </Link>
                        </Button>
                    ) : null}
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
                                <>
                                    <div className="grid gap-2">
                                        <Label>Branch</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={BranchAreasController.branchOptions.url()}
                                            value={
                                                branchFilter === 'all'
                                                    ? null
                                                    : branchFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'branch_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={
                                                selectedBranch?.name ?? null
                                            }
                                            placeholder="All branches"
                                            searchPlaceholder="Search branches…"
                                            emptyText="No branch found."
                                            noneLabel="All branches"
                                            descriptionKey={null}
                                            className="w-52"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Status</Label>
                                        <Select
                                            value={String(
                                                query.filters.is_active ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'is_active',
                                                    value === 'all'
                                                        ? null
                                                        : value,
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
                                </>
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
                        BranchAreasController.destroy.url(deletingId),
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
