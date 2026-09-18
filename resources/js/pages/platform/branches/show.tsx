import { Head, Link } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import {
    Clock,
    Eye,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Plus,
    Users,
} from 'lucide-react';
import { useMemo, type ReactNode } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
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
import { StatCard } from '@/components/portal/stat-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermission } from '@/hooks/use-permissions';
import {
    filteredBranchDriversUrl,
    filteredBranchStaffUrl,
} from '@/lib/branch-people-query';

type StaffRow = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    is_active: boolean;
    roles: string[];
};

type RoleOption = { value: string; label: string };

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
    logo: string | null;
    logo_url: string | null;
    status: boolean;
    users_count: number;
    phone: string | null;
    email: string | null;
    address: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    opening_time: string | null;
    closing_time: string | null;
    needs_super_admin: boolean;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function Fact({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium wrap-break-word">{value}</dd>
        </div>
    );
}

function formatCoord(value: string | number | null): string | null {
    if (value === null || value === '') {
        return null;
    }

    const numeric = typeof value === 'number' ? value : Number(value);

    if (Number.isFinite(numeric)) {
        return numeric.toFixed(7);
    }

    return String(value);
}

function formatRole(role: string): string {
    return role.replaceAll('-', ' ');
}

export default function PlatformBranchesShow({
    branch,
    table: tablePayload,
}: {
    branch: Branch;
    table: DataTablePayload<StaffRow> & {
        filterOptions?: { roles?: RoleOption[] };
    };
}) {
    const { can } = usePermission();
    const latitude = formatCoord(branch.latitude);
    const longitude = formatCoord(branch.longitude);
    const hasPin = latitude !== null && longitude !== null;
    const hours =
        branch.opening_time || branch.closing_time
            ? `${branch.opening_time ?? '—'} – ${branch.closing_time ?? '—'}`
            : null;
    const roles = tablePayload.filterOptions?.roles ?? [];
    const canManageStaff = can('manage-branch-users');

    const query = useDataTableQuery({
        indexUrl: BranchController.show.url(branch.slug),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                role: tablePayload.filters.role ?? 'all',
                is_active: tablePayload.filters.is_active ?? 'all',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const columns = useMemo<ColumnDef<StaffRow>[]>(
        () => [
            {
                id: 'person',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Person"
                        sortable
                        sortKey="name"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                meta: { truncate: false },
                cell: ({ row }) => (
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-9">
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
                accessorKey: 'email',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Email"
                        sortable
                        sortKey="email"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) => row.original.email ?? '—',
            },
            {
                id: 'roles',
                header: 'Role',
                meta: { truncate: false },
                cell: ({ row }) => {
                    if (row.original.roles.length === 0) {
                        return '—';
                    }

                    return (
                        <div className="flex flex-wrap gap-1">
                            {row.original.roles.map((role) => (
                                <Badge
                                    key={role}
                                    variant="secondary"
                                    className="capitalize"
                                >
                                    {formatRole(role)}
                                </Badge>
                            ))}
                        </div>
                    );
                },
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
            ...(canManageStaff
                ? [
                      {
                          id: 'actions',
                          header: 'Actions',
                          cell: ({ row }) => (
                              <DataTableRowActions
                                  items={[
                                      {
                                          label: 'View',
                                          icon: Eye,
                                          href: BranchStaffController.show.url(
                                              row.original.id,
                                          ),
                                      },
                                      {
                                          label: 'Edit',
                                          icon: Pencil,
                                          href: BranchStaffController.edit.url(
                                              row.original.id,
                                          ),
                                      },
                                  ]}
                              />
                          ),
                      } satisfies ColumnDef<StaffRow>,
                  ]
                : []),
        ],
        [canManageStaff, query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title={branch.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <Avatar className="size-14 rounded-2xl border shadow-sm">
                            {branch.logo_url ? (
                                <AvatarImage
                                    src={branch.logo_url}
                                    alt={branch.name}
                                    className="object-cover"
                                />
                            ) : null}
                            <AvatarFallback className="rounded-2xl text-base font-semibold">
                                {initials(branch.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex min-w-0 flex-col gap-2">
                            <Heading
                                title={branch.name}
                                description={`/${branch.slug}`}
                            />
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant={
                                        branch.status ? 'default' : 'secondary'
                                    }
                                >
                                    {branch.status ? 'Active' : 'Inactive'}
                                </Badge>
                                <Badge variant="outline">{branch.code}</Badge>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {can('edit-branches') ? (
                            <Button asChild>
                                <Link
                                    href={BranchController.edit.url(
                                        branch.slug,
                                    )}
                                >
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                        {canManageStaff ? (
                            <Button variant="outline" asChild>
                                <Link href={filteredBranchStaffUrl(branch.id)}>
                                    All staff
                                </Link>
                            </Button>
                        ) : null}
                        {can('manage-branch-inventories') ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={BranchInventoryController.index.url(
                                        branch.slug,
                                    )}
                                >
                                    Products
                                </Link>
                            </Button>
                        ) : null}
                        {can('manage-drivers') ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={filteredBranchDriversUrl(branch.id)}
                                >
                                    Drivers
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                {!branch.status ? (
                    <Alert>
                        <AlertTitle>Branch portal inaccessible</AlertTitle>
                        <AlertDescription>
                            This branch is inactive. Staff cannot open the
                            branch portal until you reactivate it.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {branch.needs_super_admin ? (
                    <Alert variant="destructive">
                        <AlertTitle>Branch admin required</AlertTitle>
                        <AlertDescription className="flex flex-col gap-3">
                            <p>
                                This branch cannot be managed until a branch
                                admin exists. Create one now to finish setup.
                            </p>
                            {can('create-branches') ? (
                                <Button size="sm" className="w-fit" asChild>
                                    <Link
                                        href={BranchController.createSuperAdmin.url(
                                            branch.slug,
                                        )}
                                    >
                                        Add branch admin
                                    </Link>
                                </Button>
                            ) : null}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard
                        icon={Users}
                        label="Staff"
                        value={String(branch.users_count)}
                        hint="People assigned to this branch"
                    />
                    <StatCard
                        icon={Clock}
                        label="Hours"
                        value={hours ?? 'Not set'}
                        hint="Opening hours"
                    />
                    <StatCard
                        icon={MapPin}
                        label="Location"
                        value={hasPin ? 'Pin set' : 'No pin'}
                        hint={
                            hasPin
                                ? `${latitude}, ${longitude}`
                                : 'Add coordinates from Edit'
                        }
                    />
                </div>

                <Card className="shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Contact, hours, and coordinates for this location
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Fact
                                label="Phone"
                                value={
                                    <span className="inline-flex items-center gap-2">
                                        <Phone className="text-muted-foreground size-3.5 shrink-0" />
                                        {branch.phone ?? '—'}
                                    </span>
                                }
                            />
                            <Fact
                                label="Email"
                                value={
                                    <span className="inline-flex items-center gap-2">
                                        <Mail className="text-muted-foreground size-3.5 shrink-0" />
                                        {branch.email ?? '—'}
                                    </span>
                                }
                            />
                            <Fact
                                label="Hours"
                                value={
                                    <span className="inline-flex items-center gap-2">
                                        <Clock className="text-muted-foreground size-3.5 shrink-0" />
                                        {hours ?? '—'}
                                    </span>
                                }
                            />
                            <Fact
                                label="Address"
                                value={branch.address ?? '—'}
                            />
                            <Fact
                                label="Latitude"
                                value={
                                    <span className="tabular-nums">
                                        {latitude ?? '—'}
                                    </span>
                                }
                            />
                            <Fact
                                label="Longitude"
                                value={
                                    <span className="tabular-nums">
                                        {longitude ?? '—'}
                                    </span>
                                }
                            />
                        </dl>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Staff"
                        description="Search, filter, and page through everyone assigned to this branch"
                    />
                    {canManageStaff ? (
                        <Button size="sm" asChild>
                            <Link
                                href={BranchScopedStaffController.create.url(
                                    branch.slug,
                                )}
                            >
                                <Plus className="size-4" />
                                Add staff
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No staff match this filter."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search name, phone, or email…"
                            onClear={query.clearFilters}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Role</Label>
                                        <Select
                                            value={String(
                                                query.filters.role ?? 'all',
                                            )}
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'role',
                                                    value === 'all'
                                                        ? null
                                                        : value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-48">
                                                <SelectValue placeholder="Role" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All roles
                                                </SelectItem>
                                                {roles.map((role) => (
                                                    <SelectItem
                                                        key={role.value}
                                                        value={role.value}
                                                    >
                                                        {role.label}
                                                    </SelectItem>
                                                ))}
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
                                            <SelectTrigger className="w-40">
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
        </>
    );
}

PlatformBranchesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Show', href: '#' },
    ],
};
