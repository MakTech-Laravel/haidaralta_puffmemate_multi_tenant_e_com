import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    is_super_admin: boolean;
};

type PaginatedRoles = {
    data: RoleRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

type RoleFilters = {
    search: string;
    kind: string;
    users: string;
    permissions: string;
};

export default function PlatformRolesIndex({
    roles,
    filters,
}: {
    roles: PaginatedRoles;
    filters: RoleFilters;
}) {
    const [search, setSearch] = useState(filters.search);
    const [kind, setKind] = useState(filters.kind || 'any');
    const [users, setUsers] = useState(filters.users || 'any');
    const [permissions, setPermissions] = useState(filters.permissions || 'any');
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const queryPayload = () => ({
        search: search || undefined,
        kind: kind === 'any' ? undefined : kind,
        users: users === 'any' ? undefined : users,
        permissions: permissions === 'any' ? undefined : permissions,
    });

    const applyFilters = (event?: React.FormEvent) => {
        event?.preventDefault();
        router.get(RoleController.index.url({ query: queryPayload() }), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const exportUrl = RoleController.export.url({ query: queryPayload() });

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Manage platform roles and their permissions"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <a href={exportUrl}>
                                <Download className="h-4 w-4" />
                                Export CSV
                            </a>
                        </Button>
                        <Button asChild>
                            <Link href={RoleController.create.url()}>
                                Create role
                            </Link>
                        </Button>
                    </div>
                </div>

                <form
                    className="grid gap-3 md:grid-cols-2 xl:grid-cols-5"
                    onSubmit={applyFilters}
                >
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search roles"
                    />
                    <Select value={kind} onValueChange={setKind}>
                        <SelectTrigger>
                            <SelectValue placeholder="Role type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="any">Any type</SelectItem>
                            <SelectItem value="system">System</SelectItem>
                            <SelectItem value="custom">Custom</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={users} onValueChange={setUsers}>
                        <SelectTrigger>
                            <SelectValue placeholder="Users" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="any">Any users</SelectItem>
                            <SelectItem value="with">With users</SelectItem>
                            <SelectItem value="without">Without users</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={permissions} onValueChange={setPermissions}>
                        <SelectTrigger>
                            <SelectValue placeholder="Permissions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="any">Any permissions</SelectItem>
                            <SelectItem value="none">None</SelectItem>
                            <SelectItem value="some">Some</SelectItem>
                            <SelectItem value="many">Many</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Apply filters
                    </Button>
                </form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No roles match the current filters.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                roles.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="font-medium">
                                            {role.name}
                                        </TableCell>
                                        <TableCell>
                                            {role.permissions_count}
                                        </TableCell>
                                        <TableCell>{role.users_count}</TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={RoleController.show.url(
                                                        role.id,
                                                    )}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={RoleController.edit.url(
                                                        role.id,
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            {!role.is_super_admin && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-destructive"
                                                    onClick={() =>
                                                        setDeletingId(role.id)
                                                    }
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={roles.links} />
            </div>

            <ConfirmDeleteDialog
                open={deletingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingId(null);
                    }
                }}
                description="This will permanently delete the role. Roles with assigned users cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(RoleController.destroy.url(deletingId), {
                        preserveScroll: true,
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

PlatformRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
    ],
};
