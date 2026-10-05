import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, ShoppingBag } from 'lucide-react';
import { useMemo } from 'react';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
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
import { formatAppDateTime } from '@/lib/datetime';

type WholesaleInquiryRow = {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    company: string | null;
    status: string;
    status_label: string;
    created_at: string | null;
};

type StatusOption = { value: string; label: string };

export default function PlatformWholesaleInquiriesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<WholesaleInquiryRow> & {
        filterOptions?: { status?: StatusOption[] };
    };
}) {
    const query = useDataTableQuery({
        indexUrl: WholesaleInquiryController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const statusOptions = tablePayload.filterOptions?.status ?? [];

    const columns = useMemo<ColumnDef<WholesaleInquiryRow>[]>(
        () => [
            {
                accessorKey: 'full_name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Name"
                        sortable
                        sortKey="full_name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="min-w-0">
                        <DataTableTruncated className="font-medium">
                            {row.original.full_name}
                        </DataTableTruncated>
                        {row.original.company ? (
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.company}
                            </DataTableTruncated>
                        ) : null}
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'phone',
                header: 'Phone',
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ row }) => row.original.email ?? '—',
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
                            row.original.status === 'new'
                                ? 'default'
                                : 'secondary'
                        }
                    >
                        {row.original.status_label}
                    </Badge>
                ),
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Submitted"
                        sortable
                        sortKey="created_at"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) =>
                    formatAppDateTime(row.original.created_at) || '—',
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
                                href: WholesaleInquiryController.show.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Place order',
                                icon: ShoppingBag,
                                href: WholesaleInquiryController.placeOrder.url(
                                    row.original.id,
                                ),
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
            <Head title="Wholesale inquiries" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Wholesale inquiries"
                        description="Inbound wholesale form submissions from the storefront."
                    />
                    <Button asChild>
                        <Link
                            href={WholesaleInquiryController.createPlaceOrder.url()}
                        >
                            <ShoppingBag className="size-4" />
                            Place Order
                        </Link>
                    </Button>
                </div>

                <DataTableToolbar
                    search={query.search}
                    onSearchChange={query.setSearch}
                    searchPlaceholder="Search name, email, or phone…"
                    onClear={query.clearFilters}
                    filters={
                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <Select
                                value={String(query.filters.status ?? 'all')}
                                onValueChange={(value) =>
                                    query.setFilter(
                                        'status',
                                        value === 'all' ? null : value,
                                    )
                                }
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    {statusOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No wholesale inquiries yet."
                />
            </div>
        </>
    );
}

PlatformWholesaleInquiriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Wholesale inquiries',
            href: '/platform/wholesale-inquiries',
        },
    ],
};
