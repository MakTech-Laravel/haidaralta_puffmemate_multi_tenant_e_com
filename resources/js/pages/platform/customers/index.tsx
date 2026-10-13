import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { CircleCheck, CircleX, Eye, Pencil } from 'lucide-react';
import { useMemo, useState } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatAppDateTime } from '@/lib/datetime';

type CustomerRow = {
    id: number;
    name: string;
    phone: string;
    avatar_url: string | null;
    registration_status: string | null;
    registration_status_label: string;
    phone_verified: boolean;
    address: string | null;
    is_active: boolean;
    created_at: string | null;
};

type StatusToggleTarget = {
    id: number;
    name: string;
    is_active: boolean;
};

type FilterOption = { value: string; label: string };

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformCustomersIndex({
    table: tablePayload,
}: {
    table: DataTablePayload<CustomerRow> & {
        filterOptions?: {
            registration_status?: FilterOption[];
        };
    };
}) {
    const [statusTarget, setStatusTarget] = useState<StatusToggleTarget | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const query = useDataTableQuery({
        indexUrl: CustomerController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                registration_status:
                    tablePayload.filters.registration_status ?? 'all',
                phone_verified: tablePayload.filters.phone_verified ?? 'all',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const registrationOptions =
        tablePayload.filterOptions?.registration_status ?? [];

    const columns = useMemo<ColumnDef<CustomerRow>[]>(
        () => [
            {
                id: 'customer',
                header: 'Customer',
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-10">
                            <AvatarImage
                                src={row.original.avatar_url ?? undefined}
                                alt={row.original.name}
                            />
                            <AvatarFallback>
                                {initials(row.original.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                            <DataTableTruncated className="font-medium">
                                {row.original.name}
                            </DataTableTruncated>
                            <DataTableTruncated className="text-muted-foreground text-xs">
                                {row.original.phone}
                            </DataTableTruncated>
                        </div>
                    </div>
                ),
            },
            {
                accessorKey: 'registration_status',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Registration"
                        sortable
                        sortKey="registration_status"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.registration_status === 'registered'
                                ? 'secondary'
                                : 'outline'
                        }
                    >
                        {row.original.registration_status_label}
                    </Badge>
                ),
            },
            {
                id: 'phone_verified',
                header: 'Phone',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.phone_verified
                                ? 'secondary'
                                : 'outline'
                        }
                    >
                        {row.original.phone_verified
                            ? 'Verified'
                            : 'Unverified'}
                    </Badge>
                ),
            },
            {
                accessorKey: 'address',
                header: 'Address',
                cell: ({ row }) => (
                    <DataTableTruncated>
                        {row.original.address ?? '—'}
                    </DataTableTruncated>
                ),
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
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Joined"
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
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        items={[
                            {
                                label: 'Details',
                                icon: Eye,
                                href: CustomerController.show.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: 'Edit',
                                icon: Pencil,
                                href: CustomerController.edit.url(
                                    row.original.id,
                                ),
                            },
                            {
                                label: row.original.is_active
                                    ? 'Deactivate'
                                    : 'Activate',
                                icon: row.original.is_active
                                    ? CircleX
                                    : CircleCheck,
                                destructive: row.original.is_active,
                                separatorBefore: true,
                                onSelect: () =>
                                    setStatusTarget({
                                        id: row.original.id,
                                        name: row.original.name,
                                        is_active: row.original.is_active,
                                    }),
                            },
                        ]}
                    />
                ),
            },
        ],
        [query.sort, query.toggleSort],
    );

    const deactivating = statusTarget?.is_active === true;

    return (
        <>
            <Head title="Customers" />

            <div className="space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Customers"
                        description="Registered and provisional shopper accounts."
                    />
                    <Button asChild>
                        <Link href={CustomerController.create.url()}>
                            Add Customer
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No customers found."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, phone, or email…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Registration</Label>
                                        <Select
                                            value={String(
                                                query.filters
                                                    .registration_status ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'registration_status',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-44">
                                                <SelectValue placeholder="Registration" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All registrations
                                                </SelectItem>
                                                {registrationOptions.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Phone</Label>
                                        <Select
                                            value={String(
                                                query.filters.phone_verified ??
                                                    'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'phone_verified',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-44">
                                                <SelectValue placeholder="Phone" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All phones
                                                </SelectItem>
                                                <SelectItem value="verified">
                                                    Verified
                                                </SelectItem>
                                                <SelectItem value="unverified">
                                                    Unverified
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
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
                open={statusTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setStatusTarget(null);
                    }
                }}
                title={
                    deactivating
                        ? 'Deactivate customer?'
                        : 'Activate customer?'
                }
                description={
                    deactivating
                        ? `${statusTarget?.name ?? 'This customer'} will not be able to sign in.`
                        : `${statusTarget?.name ?? 'This customer'} will be able to sign in again.`
                }
                confirmLabel={deactivating ? 'Deactivate' : 'Activate'}
                processing={processing}
                onConfirm={() => {
                    if (statusTarget === null) {
                        return;
                    }

                    setProcessing(true);
                    router.post(
                        CustomerController.toggleStatus.url(statusTarget.id),
                        {},
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setProcessing(false);
                                setStatusTarget(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformCustomersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Customers', href: '/platform/customers' },
    ],
};
