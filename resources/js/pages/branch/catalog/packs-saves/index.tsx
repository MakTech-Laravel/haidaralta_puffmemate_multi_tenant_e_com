import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { useMemo } from 'react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
import {
    DataTable,
    DataTableRowActions,
    DataTableTruncated,
    toClientDataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

type PackRow = {
    id: number;
    name: string;
    image_url: string | null;
    product_count: number;
    subtotal: string;
    pack_price: string;
    offer_type: string;
    offer_value: string;
    status: boolean;
    starts_at: string | null;
    ends_at: string | null;
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
    packs,
}: {
    branch: Branch;
    packs: PackRow[];
}) {
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

    const payload = useMemo(() => toClientDataTablePayload(packs), [packs]);

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
                header: 'Pack',
                cell: ({ row }) => (
                    <DataTableTruncated className="font-medium">
                        {row.original.name}
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
                accessorKey: 'status',
                header: 'Status',
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
                cell: () => (
                    <DataTableRowActions
                        items={[
                            { label: 'View', disabled: true },
                            { label: 'Edit', disabled: true },
                        ]}
                    />
                ),
            },
        ],
        [],
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
                    payload={payload}
                    perPage={payload.per_page}
                    onPerPageChange={() => {}}
                    showPagination={false}
                    emptyMessage="No packs yet. Create a pack to get started."
                />
            </div>
        </>
    );
}
