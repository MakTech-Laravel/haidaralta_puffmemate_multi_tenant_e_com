import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Branch/RoleController';
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

type Branch = {
    id: number;
    name: string;
    slug: string;
};

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

export default function BranchRolesShow({
    branch,
    role,
    permissionGroups,
}: {
    branch: Branch;
    role: Role;
    permissionGroups: CoverageGroup[];
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Roles', href: `/branch/${branch.slug}/roles` },
            { title: 'Show', href: '#' },
        ],
    });

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
                            <Link href={RoleController.index.url(branch.slug)}>
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={RoleController.edit.url({
                                    branch: branch.slug,
                                    role: role.id,
                                })}
                            >
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
                    router.delete(
                        RoleController.destroy.url({
                            branch: branch.slug,
                            role: role.id,
                        }),
                        {
                            preserveScroll: true,
                            onFinish: () => setProcessing(false),
                        },
                    );
                }}
            />
        </>
    );
}
