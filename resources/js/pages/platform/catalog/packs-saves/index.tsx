import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Star, StarOff, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import PacksSavesController from '@/actions/App/Http/Controllers/Platform/PacksSavesController';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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

type PackRow = {
    id: number;
    name: string;
    slug: string;
    image_url: string | null;
    branch_name: string;
    product_count: number;
    subtotal: string;
    pack_price: string;
    quantity: number;
    status: boolean;
    starts_at: string | null;
    ends_at: string | null;
    is_highlighted: boolean;
};

type SelectedBranch = { id: number; name: string; slug: string };

function initials(name: string): string {
    return name.slice(0, 2).toUpperCase();
}

function formatAud(value: string): string {
    const amount = Number(value);

    return Number.isFinite(amount) ? `$${amount.toFixed(2)}` : value;
}

function windowLabel(row: PackRow): string {
    if (!row.starts_at && !row.ends_at) {
        return 'Always on';
    }

    if (row.starts_at && row.ends_at) {
        return `${row.starts_at} – ${row.ends_at}`;
    }

    if (row.starts_at) {
        return `From ${row.starts_at}`;
    }

    return `Until ${row.ends_at}`;
}

function setHighlighted(pack: PackRow, highlighted: boolean): void {
    router.put(
        PacksSavesController.highlight.url(pack.id),
        { highlighted },
        { preserveScroll: true },
    );
}

export default function PlatformPacksSavesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<PackRow> & {
        filterOptions?: { selected_branch?: SelectedBranch | null };
    };
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: PacksSavesController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                status: tablePayload.filters.status ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const selectedBranch = tablePayload.filterOptions?.selected_branch;
    const branchFilter = String(query.filters.branch_id ?? 'all');

    const columns = useMemo<ColumnDef<PackRow>[]>(
        () => [
            {
                id: 'image',
                header: 'Image',
                cell: ({ row }) => (
                    <Avatar className="size-10 rounded-md">
                        <AvatarImage
                            src={row.original.image_url ?? undefined}
                            alt={row.original.name}
                        />
                        <AvatarFallback className="rounded-md">
                            {initials(row.original.name)}
                        </AvatarFallback>
                    </Avatar>
                ),
            },
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Pack"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-2">
                        <DataTableTruncated className="font-medium">
                            {row.original.name}
                        </DataTableTruncated>
                        {row.original.is_highlighted ? (
                            <Badge className="shrink-0">
                                <Star className="size-3 fill-current" />
                                Highlighted
                            </Badge>
                        ) : null}
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'branch_name',
                header: 'Branch',
                cell: ({ row }) => (
                    <DataTableTruncated>
                        {row.original.branch_name || '—'}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'product_count',
                header: 'Products',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.product_count}
                    </span>
                ),
            },
            {
                id: 'price',
                header: 'Pack price',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        <s className="text-muted-foreground">
                            {formatAud(row.original.subtotal)}
                        </s>{' '}
                        {formatAud(row.original.pack_price)}
                    </span>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'quantity',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Qty"
                        sortable
                        sortKey="quantity"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.quantity}
                    </span>
                ),
            },
            {
                accessorKey: 'status',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Status"
                        sortable
                        sortKey="status"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.status ? 'default' : 'secondary'
                        }
                    >
                        {row.original.status ? 'Active' : 'Inactive'}
                    </Badge>
                ),
                meta: { truncate: false },
            },
            {
                id: 'window',
                header: 'Dates',
                cell: ({ row }) => (
                    <DataTableTruncated>
                        {windowLabel(row.original)}
                    </DataTableTruncated>
                ),
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'View',
                                icon: Eye,
                                href: PacksSavesController.show.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: PacksSavesController.edit.url(
                                    row.original.id,
                                ),
                            },
                            row.original.is_highlighted
                                ? {
                                      label: 'Remove highlight',
                                      icon: StarOff,
                                      onSelect: () =>
                                          setHighlighted(row.original, false),
                                  }
                                : {
                                      label: 'Highlight',
                                      icon: Star,
                                      onSelect: () =>
                                          setHighlighted(row.original, true),
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
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Packs & Saves" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Packs & Saves"
                        description="Every branch's packs. Highlight one to feature it on the storefront."
                    />
                    <Button asChild>
                        <Link href={PacksSavesController.create.url()}>
                            Create pack
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No packs yet. Create a pack to get started."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search packs…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Branch</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={PacksSavesController.branchOptions.url()}
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
                                                query.filters.status ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'status',
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
                description="This releases reserved stock back to each product at the pack's branch and permanently deletes the pack."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(PacksSavesController.destroy.url(deletingId), {
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingId(null);
                        },
                    });
                }}
            />
        </>
    );
}
