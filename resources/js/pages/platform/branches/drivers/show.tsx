import { Head, Link, setLayoutProps } from '@inertiajs/react';
import BranchDriverController from '@/actions/App/Http/Controllers/Platform/BranchDriverController';
import PlatformDriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import Heading from '@/components/heading';
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
    assignments: {
        id: number;
        status_label: string | null;
        branch_name: string | null;
    }[];
    rosters: {
        id: number;
        date: string | null;
        start_time: string | null;
        end_time: string | null;
        status_label: string | null;
    }[];
};

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
                href: BranchDriverController.index.url(branch.slug),
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

            <div className="mx-auto max-w-4xl space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={driver.name}
                        description={`${driver.phone}${driver.email ? ` · ${driver.email}` : ''}`}
                    />
                    <div className="flex gap-2">
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

                <Card>
                    <CardHeader>
                        <CardTitle>Profile</CardTitle>
                        <CardDescription>
                            Assignment at {branch.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">
                                Status
                            </span>
                            <Badge
                                variant={
                                    driver.is_active ? 'default' : 'secondary'
                                }
                            >
                                {driver.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">
                                Vehicle
                            </span>
                            <span>{driver.vehicle_type_label ?? '—'}</span>
                        </div>
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">
                                Availability
                            </span>
                            <span>
                                {driver.availability_status_label ?? '—'}
                            </span>
                        </div>
                        {driver.assignments[0] ? (
                            <div className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Assignment
                                </span>
                                <Badge variant="secondary">
                                    {driver.assignments[0].status_label}
                                </Badge>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent rosters</CardTitle>
                        <CardDescription>
                            Manage shifts from the full driver profile
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {driver.rosters.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No roster shifts at this branch yet.
                            </p>
                        ) : (
                            driver.rosters.map((roster) => (
                                <div
                                    key={roster.id}
                                    className="rounded-md border px-3 py-2 text-sm"
                                >
                                    <div className="font-medium">
                                        {roster.date}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {roster.start_time} – {roster.end_time}{' '}
                                        · {roster.status_label}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
