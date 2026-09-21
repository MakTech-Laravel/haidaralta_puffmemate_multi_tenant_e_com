import { Head, Link, router } from '@inertiajs/react';
import {
    Check,
    Lock,
    Mail,
    Phone,
    ShieldCheck,
    X as XIcon,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import AdminController from '@/actions/App/Http/Controllers/Platform/AdminController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { formatAppDateTime } from '@/lib/datetime';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

type PermissionGroup = {
    group: string;
    total: number;
    granted_count: number;
    permissions: {
        value: string;
        label: string;
        granted: boolean;
    }[];
};

type Admin = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    is_active: boolean;
    roles: string[];
    is_super_admin: boolean;
    effective_permissions: string[];
    permission_groups: PermissionGroup[];
    phone_verified_at: string | null;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    created_at: string | null;
    updated_at: string | null;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return formatAppDateTime(value) || '—';
}

function formatRoleName(role: string): string {
    return role.replaceAll('-', ' ');
}

function FactRow({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <div className="text-muted-foreground mt-0.5 shrink-0">{icon}</div>
            <div className="min-w-0 flex-1">
                <p className="text-muted-foreground text-xs">{label}</p>
                <p className="text-sm font-medium wrap-break-word">{value}</p>
            </div>
        </div>
    );
}

function VerifiedMark({ ok }: { ok: boolean }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs font-medium',
                ok
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-muted-foreground',
            )}
        >
            {ok ? (
                <Check className="size-3.5" />
            ) : (
                <XIcon className="size-3.5" />
            )}
            {ok ? 'Verified' : 'Not verified'}
        </span>
    );
}

export default function PlatformAdminsShow({ admin }: { admin: Admin }) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const grantedCount = admin.permission_groups.reduce(
        (sum, group) => sum + group.granted_count,
        0,
    );
    const totalCount = admin.permission_groups.reduce(
        (sum, group) => sum + group.total,
        0,
    );

    return (
        <>
            <Head title={admin.name} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link href={AdminController.index.url()}>
                            All admins
                        </Link>
                    </Button>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild>
                            <Link href={AdminController.edit.url(admin.id)}>
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setConfirmOpen(true)}
                        >
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
                    <aside className="bg-card rounded-2xl border">
                        <div className="flex flex-col items-center gap-3 px-6 pt-8 pb-6 text-center">
                            <Avatar className="size-24 border shadow-sm">
                                {admin.avatar_url ? (
                                    <AvatarImage
                                        src={admin.avatar_url}
                                        alt={admin.name}
                                        className="object-cover"
                                    />
                                ) : null}
                                <AvatarFallback className="text-xl font-semibold">
                                    {initials(admin.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="space-y-2">
                                <h1 className="text-xl font-semibold tracking-tight">
                                    {admin.name}
                                </h1>
                                <Badge
                                    variant={
                                        admin.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {admin.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                        </div>
                        <Separator />
                        <div className="flex flex-col gap-4 px-6 py-5">
                            <FactRow
                                icon={<Phone className="size-4" />}
                                label="Phone"
                                value={
                                    <span className="flex flex-col gap-1">
                                        <span>{admin.phone}</span>
                                        <VerifiedMark
                                            ok={
                                                admin.phone_verified_at !== null
                                            }
                                        />
                                    </span>
                                }
                            />
                            <FactRow
                                icon={<Mail className="size-4" />}
                                label="Email"
                                value={
                                    <span className="flex flex-col gap-1">
                                        <span>{admin.email ?? '—'}</span>
                                        <VerifiedMark
                                            ok={
                                                admin.email_verified_at !== null
                                            }
                                        />
                                    </span>
                                }
                            />
                            <FactRow
                                icon={<ShieldCheck className="size-4" />}
                                label="Two-factor"
                                value={
                                    admin.two_factor_enabled ? 'Enabled' : 'Off'
                                }
                            />
                        </div>
                        <Separator />
                        <dl className="grid grid-cols-2 gap-4 px-6 py-5">
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Created
                                </dt>
                                <dd className="text-sm font-medium">
                                    {formatDateTime(admin.created_at)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground text-xs">
                                    Updated
                                </dt>
                                <dd className="text-sm font-medium">
                                    {formatDateTime(admin.updated_at)}
                                </dd>
                            </div>
                        </dl>
                    </aside>

                    <div className="flex min-w-0 flex-col gap-6">
                        <section className="bg-card overflow-hidden rounded-2xl border">
                            <div className="px-5 py-4">
                                <h2 className="text-base font-semibold tracking-tight">
                                    Role
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Access is inherited from this role
                                </p>
                            </div>
                            <Separator />
                            <ul>
                                {admin.roles.map((role) => (
                                    <li
                                        key={role}
                                        className="flex items-center justify-between gap-3 border-b px-5 py-3 last:border-b-0"
                                    >
                                        <span className="min-w-0 truncate text-sm font-medium capitalize">
                                            {formatRoleName(role)}
                                        </span>
                                        {role === 'super-admin' ? (
                                            <span className="text-muted-foreground inline-flex items-center gap-1 text-xs">
                                                <Lock className="size-3.5" />
                                                Gate bypass
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground text-xs">
                                                {role}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <section className="bg-card overflow-hidden rounded-2xl border">
                            <div className="flex items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <h2 className="text-base font-semibold tracking-tight">
                                        Permissions
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {admin.is_super_admin
                                            ? 'Super-admin bypasses permission checks via Gate.'
                                            : 'Effective access from this role'}
                                    </p>
                                </div>
                                <p className="text-muted-foreground text-sm tabular-nums">
                                    {grantedCount}/{totalCount}
                                </p>
                            </div>
                            <Separator />
                            <div className="max-h-128 overflow-y-auto">
                                {admin.permission_groups.map((group) => (
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
                    </div>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete “${admin.name}” permanently?`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(AdminController.destroy.url(admin.id), {
                        preserveScroll: true,
                        onFinish: () => setProcessing(false),
                    });
                }}
            />
        </>
    );
}

PlatformAdminsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Admins', href: '/platform/admins' },
        { title: 'Show', href: '#' },
    ],
};
