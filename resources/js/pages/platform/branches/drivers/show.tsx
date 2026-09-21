import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { IdCard, Mail, MapPin, Phone } from 'lucide-react';
import type { ReactNode } from 'react';
import BranchDriverController from '@/actions/App/Http/Controllers/Platform/BranchDriverController';
import PlatformDriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { ShiftAssignmentList } from '@/components/admin/workforce-timeline';
import type { ShiftAssignmentRow } from '@/components/admin/workforce-types';
import Heading from '@/components/heading';
import { filteredBranchDriversUrl } from '@/lib/branch-people-query';
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

type Branch = { id: number; name: string; slug: string };

type Driver = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    license_number: string | null;
    vehicle_type_label: string | null;
    availability_status_label: string | null;
    effective_availability_label: string | null;
    address: string | null;
    avatar_url: string | null;
    vehicle_image_url: string | null;
    assignments: {
        id: number;
        status_label: string | null;
        branch_name: string | null;
    }[];
    current_duty: ShiftAssignmentRow | null;
    shift_assignments: ShiftAssignmentRow[];
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

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

export default function PlatformBranchDriversShow({
    branch,
    driver,
}: {
    branch: Branch;
    driver: Driver;
    rosterStatuses: { value: string; label: string }[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Drivers',
                href: filteredBranchDriversUrl(branch.id),
            },
            {
                title: driver.name,
                href: BranchDriverController.show.url({
                    branch: branch.slug,
                    user: driver.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={driver.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <Avatar className="size-16 rounded-2xl border shadow-sm">
                            {driver.avatar_url ? (
                                <AvatarImage
                                    src={driver.avatar_url}
                                    alt={driver.name}
                                    className="object-cover"
                                />
                            ) : null}
                            <AvatarFallback className="rounded-2xl text-base font-semibold">
                                {initials(driver.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex min-w-0 flex-col gap-2">
                            <Heading
                                title={driver.name}
                                description={`${driver.phone}${driver.email ? ` · ${driver.email}` : ''}`}
                            />
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant={
                                        driver.is_active
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {driver.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                                <Badge variant="outline">
                                    {driver.effective_availability_label ??
                                        driver.availability_status_label ??
                                        'Offline'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={BranchDriverController.edit.url({
                                    branch: branch.slug,
                                    user: driver.id,
                                })}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={PlatformDriverController.show.url(
                                    driver.id,
                                )}
                            >
                                Full driver profile
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                            <CardDescription>
                                Assignment at {branch.name}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <DetailItem
                                    label="Phone"
                                    icon={<Phone className="size-4" />}
                                    value={driver.phone}
                                />
                                <DetailItem
                                    label="Email"
                                    icon={<Mail className="size-4" />}
                                    value={driver.email ?? '—'}
                                />
                                <DetailItem
                                    label="Address"
                                    icon={<MapPin className="size-4" />}
                                    value={driver.address ?? '—'}
                                />
                                <DetailItem
                                    label="License"
                                    icon={<IdCard className="size-4" />}
                                    value={driver.license_number ?? '—'}
                                />
                                <DetailItem
                                    label="Vehicle"
                                    value={driver.vehicle_type_label ?? '—'}
                                />
                                <DetailItem
                                    label="Assignment"
                                    value={
                                        driver.assignments[0]?.status_label ??
                                        '—'
                                    }
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Vehicle</CardTitle>
                            <CardDescription>
                                Photo used on this driver profile
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {driver.vehicle_image_url ? (
                                <img
                                    src={driver.vehicle_image_url}
                                    alt={`${driver.name} vehicle`}
                                    className="border-border/70 h-56 w-full rounded-xl border object-cover"
                                />
                            ) : (
                                <div className="border-border/70 bg-muted/30 flex min-h-36 flex-col items-center justify-center rounded-xl border border-dashed px-4 py-8 text-center">
                                    <p className="text-sm font-medium">
                                        No vehicle photo
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Duty windows</CardTitle>
                        <CardDescription>
                            Shift assignments at {branch.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ShiftAssignmentList
                            assignments={driver.shift_assignments}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
