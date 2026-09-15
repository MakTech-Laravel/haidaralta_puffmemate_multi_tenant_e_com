import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { DriverBranchAssignSheet } from '@/components/admin/driver-branch-assign-sheet';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { TimePicker } from '@/components/ui/time-picker';

type Option = { value: string; label: string };

type Driver = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    license_number: string | null;
    vehicle_type_label: string | null;
    availability_status: string | null;
    availability_status_label: string | null;
    effective_availability_label: string | null;
    notes: string | null;
    address: string | null;
    avatar_url: string | null;
    vehicle_image_url: string | null;
    branch_ids: number[];
    assignments: {
        id: number;
        branch_id: number;
        branch_name: string | null;
        branch_slug: string | null;
        status: string | null;
        status_label: string | null;
    }[];
    rosters: {
        id: number;
        branch_id: number;
        branch_name: string | null;
        date: string | null;
        start_time: string | null;
        end_time: string | null;
        status: string | null;
        status_label: string | null;
    }[];
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function PlatformDriversShow({
    driver,
    rosterStatuses,
}: {
    driver: Driver;
    branches: { id: number; name: string; slug: string }[];
    rosterStatuses: Option[];
    assignmentStatuses: Option[];
}) {
    const activeAssignments = useMemo(
        () =>
            driver.assignments.filter(
                (assignment) => assignment.status === 'active',
            ),
        [driver.assignments],
    );

    const [branchSheetOpen, setBranchSheetOpen] = useState(false);
    const [branchId, setBranchId] = useState(
        String(activeAssignments[0]?.branch_id ?? ''),
    );
    const [date, setDate] = useState('');
    const [startTime, setStartTime] = useState('');
    const [endTime, setEndTime] = useState('');
    const [rosterStatus, setRosterStatus] = useState(
        rosterStatuses.find((item) => item.value === 'scheduled')?.value ??
            rosterStatuses[0]?.value ??
            '',
    );

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Drivers', href: DriverController.index.url() },
            {
                title: driver.name,
                href: DriverController.show.url(driver.id),
            },
        ],
    });

    return (
        <>
            <Head title={driver.name} />

            <div className="mx-auto max-w-5xl space-y-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <Avatar className="size-16">
                            <AvatarImage
                                src={driver.avatar_url ?? undefined}
                                alt={driver.name}
                            />
                            <AvatarFallback className="text-lg">
                                {initials(driver.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 space-y-2">
                            <Heading
                                title={driver.name}
                                description={`${driver.phone}${driver.email ? ` · ${driver.email}` : ''}`}
                            />
                            <div className="flex flex-wrap gap-2">
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
                                        'Offline'}
                                </Badge>
                                {driver.vehicle_type_label ? (
                                    <Badge variant="secondary">
                                        {driver.vehicle_type_label}
                                    </Badge>
                                ) : null}
                            </div>
                        </div>
                        {driver.vehicle_image_url ? (
                            <img
                                src={driver.vehicle_image_url}
                                alt={`${driver.name} vehicle`}
                                className="border-border/60 ml-auto hidden h-20 w-28 rounded-lg border object-cover sm:block"
                            />
                        ) : null}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={DriverController.edit.url(driver.id)}>
                                Edit
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => setBranchSheetOpen(true)}
                        >
                            <Building2 className="size-4" />
                            Manage branches
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={DriverController.index.url()}>
                                All drivers
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                            <CardDescription>
                                Address, license, and availability override
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Address
                                </span>
                                <span className="text-right">
                                    {driver.address ?? '—'}
                                </span>
                            </div>
                            <div className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    License
                                </span>
                                <span>{driver.license_number ?? '—'}</span>
                            </div>
                            <div className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Vehicle
                                </span>
                                <span>
                                    {driver.vehicle_type_label ?? '—'}
                                </span>
                            </div>
                            <div className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    Override status
                                </span>
                                <span>
                                    {driver.availability_status_label ?? '—'}
                                </span>
                            </div>
                            {driver.notes ? (
                                <p className="text-muted-foreground pt-2">
                                    {driver.notes}
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle>Branch assignments</CardTitle>
                            <CardDescription>
                                Active and historical branch links
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {driver.assignments.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No branch assignments yet.
                                </p>
                            ) : (
                                driver.assignments.map((assignment) => (
                                    <div
                                        key={assignment.id}
                                        className="flex items-center justify-between gap-2 text-sm"
                                    >
                                        {assignment.branch_slug ? (
                                            <Link
                                                href={BranchController.show.url(
                                                    assignment.branch_slug,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {assignment.branch_name ??
                                                    'Unknown branch'}
                                            </Link>
                                        ) : (
                                            <span>
                                                {assignment.branch_name ??
                                                    'Unknown branch'}
                                            </span>
                                        )}
                                        <Badge variant="secondary">
                                            {assignment.status_label}
                                        </Badge>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-none">
                    <CardHeader>
                        <CardTitle>Rosters</CardTitle>
                        <CardDescription>
                            Shift duty times by branch and date
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Form
                            action={DriverController.storeRoster.url(
                                driver.id,
                            )}
                            method="post"
                            className="grid gap-3 md:grid-cols-5"
                            options={{ preserveScroll: true }}
                            onSuccess={() => {
                                setDate('');
                                setStartTime('');
                                setEndTime('');
                            }}
                        >
                            {({ errors, processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="branch_id"
                                        value={branchId}
                                    />
                                    <input type="hidden" name="date" value={date} />
                                    <input
                                        type="hidden"
                                        name="start_time"
                                        value={startTime}
                                    />
                                    <input
                                        type="hidden"
                                        name="end_time"
                                        value={endTime}
                                    />
                                    <input
                                        type="hidden"
                                        name="status"
                                        value={rosterStatus}
                                    />

                                    <div className="grid gap-2">
                                        <Label>Branch</Label>
                                        <Select
                                            value={branchId}
                                            onValueChange={setBranchId}
                                            disabled={
                                                activeAssignments.length === 0
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Branch" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {activeAssignments.map(
                                                    (assignment) => (
                                                        <SelectItem
                                                            key={
                                                                assignment.branch_id
                                                            }
                                                            value={String(
                                                                assignment.branch_id,
                                                            )}
                                                        >
                                                            {assignment.branch_name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.branch_id}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Date</Label>
                                        <DatePicker
                                            value={date}
                                            onChange={setDate}
                                        />
                                        <InputError message={errors.date} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Start</Label>
                                        <TimePicker
                                            value={startTime}
                                            onChange={setStartTime}
                                        />
                                        <InputError
                                            message={errors.start_time}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>End</Label>
                                        <TimePicker
                                            value={endTime}
                                            onChange={setEndTime}
                                        />
                                        <InputError
                                            message={errors.end_time}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Status</Label>
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
                                                disabled={
                                                    processing ||
                                                    activeAssignments.length ===
                                                        0
                                                }
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
                                                {roster.branch_name} ·{' '}
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

            <DriverBranchAssignSheet
                open={branchSheetOpen}
                onOpenChange={setBranchSheetOpen}
                driverId={driver.id}
                selectedIds={driver.branch_ids}
            />
        </>
    );
}
