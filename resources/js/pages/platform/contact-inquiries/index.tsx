import { Head } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye } from 'lucide-react';
import { useMemo } from 'react';
import ContactInquiryController from '@/actions/App/Http/Controllers/Platform/ContactInquiryController';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatAppDateTime } from '@/lib/datetime';

type ContactInquiryRow = {
    id: number;
    full_name: string;
    phone: string;
    email: string | null;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    created_at: string | null;
};

type FilterOption = { value: string; label: string };

export default function PlatformContactInquiriesIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<ContactInquiryRow> & {
        filterOptions?: { status?: FilterOption[]; type?: FilterOption[] };
    };
}) {
    const query = useDataTableQuery({
        indexUrl: ContactInquiryController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                status: tablePayload.filters.status ?? 'all',
                type: tablePayload.filters.type ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const statusOptions = tablePayload.filterOptions?.status ?? [];
    const typeOptions = tablePayload.filterOptions?.type ?? [];

    const columns = useMemo<ColumnDef<ContactInquiryRow>[]>(
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
                    <DataTableTruncated className="font-medium">
                        {row.original.full_name}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'phone',
                header: 'Phone',
                cell: ({ row }) => row.original.phone,
            },
            {
                accessorKey: 'email',
                header: 'Email',
                cell: ({ row }) => row.original.email || '—',
            },
            {
                accessorKey: 'type',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Type"
                        sortable
                        sortKey="type"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.type_label,
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
                                href: ContactInquiryController.show.url(
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
            <Head title="Contact inquiries" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Contact inquiries"
                    description="Inbound contact form submissions from the storefront."
                />

                <DataTableToolbar
                    search={query.search}
                    onSearchChange={query.setSearch}
                    searchPlaceholder="Search name, email, or phone…"
                    onClear={query.clearFilters}
                    filters={
                        <div className="flex flex-wrap gap-4">
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={String(
                                        query.filters.status ?? 'all',
                                    )}
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
                            <div className="grid gap-2">
                                <Label>Type</Label>
                                <Select
                                    value={String(query.filters.type ?? 'all')}
                                    onValueChange={(value) =>
                                        query.setFilter(
                                            'type',
                                            value === 'all' ? null : value,
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-48">
                                        <SelectValue placeholder="Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All types
                                        </SelectItem>
                                        {typeOptions.map((option) => (
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
                        </div>
                    }
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No contact inquiries yet."
                />
            </div>
        </>
    );
}

PlatformContactInquiriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Contact inquiries',
            href: '/platform/contact-inquiries',
        },
    ],
};
