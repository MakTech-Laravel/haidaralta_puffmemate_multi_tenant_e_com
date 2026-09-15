import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Option = { value: string; label: string };
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
    notes: string | null;
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

export default function BranchDriversShow({
    branch,
    driver,
    rosterStatuses,
}: {
    branch: Branch;
    driver: Driver;
    rosterStatuses: Option[];
}) {
    const [rosterStatus, setRosterStatus] = useState(
        rosterStatuses.find((item) => item.value === 'scheduled')?.value ??
            rosterStatuses[0]?.value ??
            '',
    );

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Drivers',
                href: DriverController.index.url(branch.slug),
            },
            {
                title: driver.name,
                href: DriverController.show.url({
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
                    <Button variant="outline" asChild>
                        <Link
                            href={DriverController.edit.url({
                                branch: branch.slug,
                                user: driver.id,
                            })}
                        >
                            Edit
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Profile</CardTitle>
                        <CardDescription>
                            Vehicle and availability for this driver
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
                        <div className="flex justify-between gap-4">
                            <span className="text-muted-foreground">
                                License
                            </span>
                            <span>{driver.license_number ?? '—'}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Rosters at {branch.name}</CardTitle>
                        <CardDescription>
                            Shift duty times for this branch
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Form
                            action={DriverController.storeRoster.url({
                                branch: branch.slug,
                                user: driver.id,
                            })}
                            method="post"
                            className="grid gap-3 md:grid-cols-4"
                            options={{ preserveScroll: true }}
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="date">Date</Label>
                                        <Input
                                            id="date"
                                            name="date"
                                            type="date"
                                            required
                                        />
                                        <InputError message={errors.date} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="start_time">
                                            Start
                                        </Label>
                                        <Input
                                            id="start_time"
                                            name="start_time"
                                            type="time"
                                            required
                                        />
                                        <InputError
                                            message={errors.start_time}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="end_time">End</Label>
                                        <Input
                                            id="end_time"
                                            name="end_time"
                                            type="time"
                                            required
                                        />
                                        <InputError
                                            message={errors.end_time}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Status</Label>
                                        <input
                                            type="hidden"
                                            name="status"
                                            value={rosterStatus}
                                        />
                                        <div className="flex gap-2">
                                            <Select
                                                value={rosterStatus}
                                                onValueChange={setRosterStatus}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {rosterStatuses.map(
                                                        (status) => (
                                                            <SelectItem
                                                                key={
                                                                    status.value
                                                                }
                                                                value={
                                                                    status.value
                                                                }
                                                            >
                                                                {status.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : null}
                                                Add
                                            </Button>
                                        </div>
                                    </div>
                                </>
                            )}
                        </Form>

                        <div className="space-y-2">
                            {driver.rosters.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No roster shifts yet.
                                </p>
                            ) : (
                                driver.rosters.map((roster) => (
                                    <div
                                        key={roster.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {roster.date}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {roster.start_time} –{' '}
                                                {roster.end_time} ·{' '}
                                                {roster.status_label}
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() =>
                                                router.delete(
                                                    DriverController.destroyRoster.url(
                                                        {
                                                            branch: branch.slug,
                                                            user: driver.id,
                                                            roster: roster.id,
                                                        },
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
