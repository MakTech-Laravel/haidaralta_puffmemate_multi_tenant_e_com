import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions_count: number;
    total_permissions: number;
    users: {
        id: number;
        name: string;
        email: string;
    }[];
};

type CoverageGroup = {
    group: string;
    total: number;
    granted_count: number;
    permissions: {
        value: string;
        label: string;
        granted: boolean;
    }[];
};

export default function PlatformRolesShow({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: CoverageGroup[];
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={role.name} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={role.name}
                        description={`${role.permissions_count} of ${role.total_permissions} permissions · ${role.users.length} assigned users`}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={RoleController.index.url()}>Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={RoleController.edit.url(role.id)}>
                                Edit
                            </Link>
                        </Button>
                        {!role.is_super_admin && role.users.length === 0 && (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmOpen(true)}
                            >
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                <section className="space-y-4">
                    <h2 className="text-sm font-medium">Permission coverage</h2>
                    {role.is_super_admin ? (
                        <p className="text-muted-foreground text-sm">
                            Super-admin bypasses all permission checks via Gate
                            and is treated as fully covered.
                        </p>
                    ) : null}
                    {permissionGroups.map((group) => (
                        <div key={group.group} className="space-y-2">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-medium">
                                    {group.group}
                                </h3>
                                <span className="text-muted-foreground text-xs">
                                    {group.granted_count}/{group.total}
                                </span>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {group.permissions.map((permission) => (
                                    <Badge
                                        key={permission.value}
                                        variant={
                                            permission.granted
                                                ? 'default'
                                                : 'outline'
                                        }
                                    >
                                        {permission.label}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    ))}
                </section>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium">Assigned users</h2>
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {role.users.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={2}>
                                            No users assigned.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    role.users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>{user.name}</TableCell>
                                            <TableCell>{user.email}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </section>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete the “${role.name}” role permanently?`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(RoleController.destroy.url(role.id), {
                        preserveScroll: true,
                        onFinish: () => setProcessing(false),
                    });
                }}
            />
        </>
    );
}

PlatformRolesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Show', href: '#' },
    ],
};
