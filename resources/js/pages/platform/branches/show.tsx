import { Head, Link } from '@inertiajs/react';
import {
    Clock,
    Mail,
    MapPin,
    Phone,
    Users,
} from 'lucide-react';
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
import { cn } from '@/lib/utils';

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
    icon,
}: {
    label: string;
    value: ReactNode;
    icon?: ReactNode;
}) {
    return (
        <div className="flex gap-3">
            {icon ? (
                <div className="bg-muted text-muted-foreground mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                    {icon}
                </div>
            ) : null}
            <div className="flex min-w-0 flex-col gap-1">
                <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                    {label}
                </dt>
                <dd className="text-sm font-medium wrap-break-word">{value}</dd>
            </div>
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

export default function PlatformBranchesShow({ branch }: { branch: Branch }) {
    const { can } = usePermission();
    const latitude = formatCoord(branch.latitude);
    const longitude = formatCoord(branch.longitude);
    const hasPin = latitude !== null && longitude !== null;
    const hours =
        branch.opening_time || branch.closing_time
            ? `${branch.opening_time ?? '—'} – ${branch.closing_time ?? '—'}`
            : null;

    return (
        <>
            <Head title={branch.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <Avatar className="size-16 rounded-2xl border shadow-sm">
                            {branch.logo_url ? (
                                <AvatarImage
                                    src={branch.logo_url}
                                    alt={branch.name}
                                    className="object-cover"
                                />
                            ) : null}
                            <AvatarFallback className="rounded-2xl text-base font-semibold">
                                {branch.name.slice(0, 2).toUpperCase()}
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
                                <Badge variant="outline" className="gap-1">
                                    <Users className="size-3" />
                                    {branch.users_count} staff
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={BranchController.index.url()}>
                                Back
                            </Link>
                        </Button>
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
                        {can('manage-branch-users') ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={BranchScopedStaffController.index.url(
                                        branch.slug,
                                    )}
                                >
                                    Staff
                                </Link>
                            </Button>
                        ) : null}
                        {can('manage-branch-inventories') ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/platform/branches/${branch.slug}/products`}
                                >
                                    Products
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

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Contact</CardTitle>
                            <CardDescription>
                                How customers and staff reach this location
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5">
                                <DetailItem
                                    label="Phone"
                                    icon={<Phone className="size-4" />}
                                    value={branch.phone ?? '—'}
                                />
                                <DetailItem
                                    label="Email"
                                    icon={<Mail className="size-4" />}
                                    value={branch.email ?? '—'}
                                />
                                <DetailItem
                                    label="Hours"
                                    icon={<Clock className="size-4" />}
                                    value={hours ?? '—'}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Map location</CardTitle>
                            <CardDescription>
                                Address and coordinates for this branch
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <DetailItem
                                label="Address"
                                icon={<MapPin className="size-4" />}
                                value={branch.address ?? '—'}
                            />

                            <div
                                className={cn(
                                    'border-border/70 bg-muted/30 flex min-h-36 flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-8 text-center',
                                )}
                            >
                                <MapPin
                                    className="text-muted-foreground size-7 opacity-70"
                                    aria-hidden
                                />
                                {hasPin ? (
                                    <>
                                        <p className="text-sm font-medium">
                                            Pin set
                                        </p>
                                        <p className="text-muted-foreground text-xs tabular-nums">
                                            {latitude}, {longitude}
                                        </p>
                                        <p className="text-muted-foreground max-w-xs text-xs">
                                            A live map preview will appear here
                                            when Mapbox or Google Maps is
                                            connected.
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <p className="text-sm font-medium">
                                            No pin yet
                                        </p>
                                        <p className="text-muted-foreground max-w-xs text-xs">
                                            Add coordinates from Edit → Map
                                            location when you are ready.
                                        </p>
                                    </>
                                )}
                            </div>

                            <dl className="grid gap-4 sm:grid-cols-2">
                                <DetailItem
                                    label="Latitude"
                                    value={latitude ?? '—'}
                                />
                                <DetailItem
                                    label="Longitude"
                                    value={longitude ?? '—'}
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                        <div className="space-y-1.5">
                            <CardTitle>Branch admins</CardTitle>
                            <CardDescription>
                                People who can sign in and manage this branch
                                portal
                            </CardDescription>
                        </div>
                        {branch.needs_super_admin && can('create-branches') ? (
                            <Button size="sm" asChild>
                                <Link
                                    href={BranchController.createSuperAdmin.url(
                                        branch.slug,
                                    )}
                                >
                                    Add branch admin
                                </Link>
                            </Button>
                        ) : can('manage-branch-users') ? (
                            <Button size="sm" variant="outline" asChild>
                                <Link
                                    href={BranchScopedStaffController.index.url(
                                        branch.slug,
                                    )}
                                >
                                    Manage staff
                                </Link>
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent>
                        {branch.super_admins.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No branch admins yet.
                            </p>
                        ) : (
                            <div className="flex flex-col">
                                {branch.super_admins.map((admin, index) => (
                                    <div key={admin.id}>
                                        {index > 0 ? (
                                            <Separator className="my-4" />
                                        ) : null}
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <Avatar className="size-10">
                                                    <AvatarFallback>
                                                        {admin.name
                                                            .slice(0, 2)
                                                            .toUpperCase()}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {admin.name}
                                                    </p>
                                                    <p className="text-muted-foreground truncate text-sm">
                                                        {admin.phone}
                                                        {admin.email
                                                            ? ` · ${admin.email}`
                                                            : null}
                                                    </p>
                                                </div>
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
