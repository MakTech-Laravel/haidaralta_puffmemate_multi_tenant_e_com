import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

type PackRow = {
    id: number;
    name: string;
    slug: string;
    image_url: string | null;
    product_count: number;
    subtotal: string;
    pack_price: string;
    quantity: number;
    status: boolean;
    starts_at: string | null;
    ends_at: string | null;
    is_highlighted: boolean;
};

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

export default function BranchPacksSavesIndex({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<PackRow>;
}) {
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(branch.slug),
            },
        ],
    });

    const query = useDataTableQuery({
        indexUrl: PacksSavesController.index.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? null,
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

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
                            <Badge variant="outline" className="shrink-0">
                                Highlighted
                            </Badge>
                        ) : null}
                    </div>
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
                                href: PacksSavesController.show.url({
                                    branch: branch.slug,
                                    pack: row.original.slug,
                                }),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: PacksSavesController.edit.url({
                                    branch: branch.slug,
                                    pack: row.original.slug,
                                }),
                            },
                            {
                                label: 'Delete',
                                icon: Trash2,
                                destructive: true,
                                separatorBefore: true,
                                onSelect: () =>
                                    setDeletingSlug(row.original.slug),
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
            <Head title="Packs & Saves" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Packs & Saves"
                        description={`Bundle ${branch.name} stock products into a pack price`}
                    />
                    <Button asChild>
                        <Link
                            href={PacksSavesController.create.url(branch.slug)}
                        >
                            Create pack
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No packs yet. Create a pack to get started."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search packs…"
                            onClear={query.clearFilters}
                        />
                    }
                />
            </div>

            <ConfirmDeleteDialog
                open={deletingSlug !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingSlug(null);
                    }
                }}
                description="This releases reserved stock back to each product and permanently deletes the pack."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        PacksSavesController.destroy.url({
                            branch: branch.slug,
                            pack: deletingSlug,
                        }),
                        {
                            onFinish: () => setProcessing(false),
                        },
                    );
                }}
            />
        </>
    );
}
