import { Link, router } from '@inertiajs/react';
import { Check, Shield, Users, X as XIcon } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type RoleProfileUser = {
    id: number;
    name: string;
    email: string | null;
};

export type RoleProfileCoverageGroup = {
    group: string;
    total: number;
    granted_count: number;
    permissions: {
        value: string;
        label: string;
        granted: boolean;
    }[];
};

export type RoleProfileRole = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions_count: number;
    total_permissions: number;
    users: RoleProfileUser[];
};

function formatRoleName(name: string): string {
    return name.replaceAll('-', ' ');
}

export function RoleProfile({
    role,
    permissionGroups,
    indexHref,
    editHref,
    destroyUrl,
    indexLabel = 'All roles',
    userHref,
}: {
    role: RoleProfileRole;
    permissionGroups: RoleProfileCoverageGroup[];
    indexHref: string;
    editHref: string;
    destroyUrl?: string;
    indexLabel?: string;
    userHref?: (user: RoleProfileUser) => string;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const canDelete = destroyUrl !== undefined;

    return (
        <>
            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={indexHref}>{indexLabel}</Link>
                    </Button>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild>
                            <Link href={editHref}>Edit</Link>
                        </Button>
                        {canDelete ? (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmOpen(true)}
                            >
                                Delete
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
                    <aside className="rounded-2xl border bg-card">
                        <div className="flex flex-col items-center gap-3 px-6 pt-8 pb-6 text-center">
                            <div className="bg-muted flex size-24 items-center justify-center rounded-2xl border shadow-sm">
                                <Shield className="text-muted-foreground size-10" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-xl font-semibold tracking-tight capitalize">
                                    {formatRoleName(role.name)}
                                </h1>
                                <div className="flex flex-wrap justify-center gap-2">
                                    <Badge
                                        variant={
                                            role.is_super_admin
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {role.is_super_admin
                                            ? 'System'
                                            : 'Custom'}
                                    </Badge>
                                    {role.is_super_admin ? (
                                        <Badge variant="outline">
                                            Gate bypass
                                        </Badge>
                                    ) : null}
                                </div>
                            </div>
                        </div>
                        <Separator />
                        <dl className="grid grid-cols-2 gap-4 px-6 py-5">
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Permissions
                                </dt>
                                <dd className="text-sm font-medium tabular-nums">
                                    {role.permissions_count}/
                                    {role.total_permissions}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground flex items-center gap-1 text-xs">
                                    <Users className="size-3.5" />
                                    Users
                                </dt>
                                <dd className="text-sm font-medium tabular-nums">
                                    {role.users.length}
                                </dd>
                            </div>
                        </dl>
                    </aside>

                    <div className="flex min-w-0 flex-col gap-6">
                        <section className="overflow-hidden rounded-2xl border bg-card">
                            <div className="flex items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <h2 className="text-base font-semibold tracking-tight">
                                        Permissions
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {role.is_super_admin
                                            ? 'Super-admin bypasses permission checks via Gate.'
                                            : 'Granted access for accounts with this role'}
                                    </p>
                                </div>
                                <p className="text-muted-foreground text-sm tabular-nums">
                                    {role.permissions_count}/
                                    {role.total_permissions}
                                </p>
                            </div>
                            <Separator />
                            <div className="max-h-128 overflow-y-auto">
                                {permissionGroups.map((group) => (
                                    <div
                                        key={group.group}
                                        className="border-b last:border-b-0"
                                    >
                                        <div className="bg-muted/30 flex items-center justify-between gap-3 px-5 py-2">
                                            <h3 className="text-sm font-medium">
                                                {group.group}
                                            </h3>
                                            <span className="text-muted-foreground text-xs tabular-nums">
                                                {group.granted_count}/
                                                {group.total}
                                            </span>
                                        </div>
                                        <ul>
                                            {group.permissions.map(
                                                (permission) => (
                                                    <li
                                                        key={permission.value}
                                                        className="flex items-center justify-between gap-3 px-5 py-2.5"
                                                    >
                                                        <span className="min-w-0 truncate text-sm">
                                                            {permission.label}
                                                        </span>
                                                        {permission.granted ? (
                                                            <Check className="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                                        ) : (
                                                            <XIcon className="text-muted-foreground/50 size-4 shrink-0" />
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-2xl border bg-card">
                            <div className="px-5 py-4">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Assigned users
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Accounts currently using this role
                                </p>
                            </div>
                            <Separator />
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
                                            <TableCell
                                                colSpan={2}
                                                className="text-muted-foreground"
                                            >
                                                No users assigned.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        role.users.map((user) => {
                                            const href = userHref?.(user);

                                            return (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-medium">
                                                        {href ? (
                                                            <Link
                                                                href={href}
                                                                className="hover:underline"
                                                            >
                                                                {user.name}
                                                            </Link>
                                                        ) : (
                                                            user.name
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {user.email ?? '—'}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </section>
                    </div>
                </div>
            </div>

            {canDelete && destroyUrl ? (
                <ConfirmDeleteDialog
                    open={confirmOpen}
                    onOpenChange={setConfirmOpen}
                    description={`Delete the “${role.name}” role permanently?`}
                    processing={processing}
                    onConfirm={() => {
                        setProcessing(true);
                        router.delete(destroyUrl, {
                            preserveScroll: true,
                            onFinish: () => setProcessing(false),
                        });
                    }}
                />
            ) : null}
        </>
    );
}
