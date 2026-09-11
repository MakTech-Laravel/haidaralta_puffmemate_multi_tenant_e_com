import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import Heading from '@/components/heading';
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
import { Separator } from '@/components/ui/separator';
import { usePermission } from '@/hooks/use-permissions';

type SuperAdmin = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
};

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
    super_admins: SuperAdmin[];
};

function DetailItem({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

export default function PlatformBranchesShow({ branch }: { branch: Branch }) {
    const { can } = usePermission();

    return (
        <>
            <Head title={branch.name} />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar className="size-14 rounded-xl">
                            {branch.logo_url ? (
                                <AvatarImage
                                    src={branch.logo_url}
                                    alt={branch.name}
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl text-base">
                                {branch.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col gap-2">
                            <Heading
                                title={branch.name}
                                description={`/${branch.slug} · ${branch.code}`}
                            />
                            <Badge
                                variant={
                                    branch.status ? 'default' : 'secondary'
                                }
                            >
                                {branch.status ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="default" asChild>
                            <Link href={BranchController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        {can('manage-branch-users') ? (
                            <Button variant="default" asChild>
                                <Link
                                    href={BranchScopedStaffController.index.url(
                                        branch.slug,
                                    )}
                                >
                                    Staff management
                                </Link>
                            </Button>
                        ) : null}
                        <Button type="button" variant="default" disabled>
                            Product management
                        </Button>
                    </div>
                </div>

                {branch.needs_super_admin ? (
                    <Alert>
                        <AlertTitle>Super-admin missing</AlertTitle>
                        <AlertDescription className="flex flex-col gap-3">
                            <p>
                                This branch has no super-admin yet. Add one to
                                finish setup.
                            </p>
                            {can('create-branches') ? (
                                <Button size="sm" className="w-fit" asChild>
                                    <Link
                                        href={BranchController.createSuperAdmin.url(
                                            branch.slug,
                                        )}
                                    >
                                        Add super-admin
                                    </Link>
                                </Button>
                            ) : null}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Contact, hours, and location for this branch
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-5 sm:grid-cols-2">
                            <DetailItem label="Code" value={branch.code} />
                            <DetailItem
                                label="Staff"
                                value={branch.users_count}
                            />
                            <DetailItem
                                label="Phone"
                                value={branch.phone ?? '—'}
                            />
                            <DetailItem
                                label="Email"
                                value={branch.email ?? '—'}
                            />
                            <DetailItem
                                label="Address"
                                value={branch.address ?? '—'}
                            />
                            <DetailItem
                                label="Hours"
                                value={
                                    branch.opening_time || branch.closing_time
                                        ? `${branch.opening_time ?? '—'} – ${branch.closing_time ?? '—'}`
                                        : '—'
                                }
                            />
                            <DetailItem
                                label="Latitude"
                                value={branch.latitude ?? '—'}
                            />
                            <DetailItem
                                label="Longitude"
                                value={branch.longitude ?? '—'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Super-admins</CardTitle>
                            <CardDescription>
                                Portal admins for this branch
                            </CardDescription>
                        </div>
                        {branch.needs_super_admin && can('create-branches') ? (
                            <Button size="sm" asChild>
                                <Link
                                    href={BranchController.createSuperAdmin.url(
                                        branch.slug,
                                    )}
                                >
                                    Add super-admin
                                </Link>
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent>
                        {branch.super_admins.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No super-admins found for this branch.
                            </p>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {branch.super_admins.map((admin, index) => (
                                    <div key={admin.id}>
                                        {index > 0 ? (
                                            <Separator className="mb-3" />
                                        ) : null}
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex flex-col gap-0.5">
                                                <p className="text-sm font-medium">
                                                    {admin.name}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    {admin.phone}
                                                    {admin.email
                                                        ? ` · ${admin.email}`
                                                        : null}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={
                                                    admin.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {admin.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
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
