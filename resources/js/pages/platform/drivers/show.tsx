import { Form, Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    IdCard,
    Mail,
    MapPin,
    Phone,
    StickyNote,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useMemo, useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { DriverBranchAssignSheet } from '@/components/admin/driver-branch-assign-sheet';
import {
    DriverRosterFields,
    emptyRosterFields,
    formatRosterPeriodLine,
} from '@/components/admin/driver-roster-fields';
import Heading from '@/components/heading';
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
import { Spinner } from '@/components/ui/spinner';

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
        start_date: string | null;
        end_date: string | null;
        start_time: string | null;
        end_time: string | null;
        days_label: string | null;
        period_label: string | null;
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
    const defaultRosterStatus =
        rosterStatuses.find((item) => item.value === 'scheduled')?.value ??
        rosterStatuses[0]?.value ??
        'scheduled';
    const [rosterDraft, setRosterDraft] = useState(() =>
        emptyRosterFields({
            branchId: String(activeAssignments[0]?.branch_id ?? ''),
            status: defaultRosterStatus,
        }),
    );

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
                                        'Offline'}
                                </Badge>
                                {driver.vehicle_type_label ? (
                                    <Badge variant="outline">
                                        {driver.vehicle_type_label}
                                    </Badge>
                                ) : null}
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={DriverController.index.url()}>
                                All drivers
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => setBranchSheetOpen(true)}
                        >
                            <Building2 className="size-4" />
                            Manage branches
                        </Button>
                        <Button asChild>
                            <Link href={DriverController.edit.url(driver.id)}>
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                            <CardDescription>
                                Address, license, and availability override
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
                                    label="Override status"
                                    value={
                                        driver.availability_status_label ?? '—'
                                    }
                                />
                            </dl>
                            {driver.notes ? (
                                <div className="mt-5">
                                    <DetailItem
                                        label="Notes"
                                        icon={<StickyNote className="size-4" />}
                                        value={driver.notes}
                                    />
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Vehicle</CardTitle>
                            <CardDescription>
                                Photo used on the driver profile
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
                                    <p className="text-muted-foreground max-w-xs text-xs">
                                        Add one from Edit so dispatchers can
                                        recognize this driver.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                        <div className="space-y-1.5">
                            <CardTitle>Branch assignments</CardTitle>
                            <CardDescription>
                                Active and historical branch links
                            </CardDescription>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setBranchSheetOpen(true)}
                        >
                            <Building2 className="size-4" />
                            Manage branches
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {driver.assignments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No branch assignments yet.
                            </p>
                        ) : (
                            <div className="flex flex-col">
                                {driver.assignments.map((assignment, index) => (
                                    <div key={assignment.id}>
                                        {index > 0 ? (
                                            <Separator className="my-4" />
                                        ) : null}
                                        <div className="flex flex-wrap items-center justify-between gap-3">
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
                                                <span className="font-medium">
                                                    {assignment.branch_name ??
                                                        'Unknown branch'}
                                                </span>
                                            )}
                                            <Badge variant="secondary">
                                                {assignment.status_label}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Rosters</CardTitle>
                        <CardDescription>
                            Shift duty times by branch and date period
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Form
                            action={DriverController.storeRoster.url(driver.id)}
                            method="post"
                            className="space-y-3 rounded-xl border p-3"
                            options={{ preserveScroll: true }}
                            onSuccess={() => {
                                setRosterDraft(
                                    emptyRosterFields({
                                        branchId:
                                            rosterDraft.branch_id ||
                                            String(
                                                activeAssignments[0]
                                                    ?.branch_id ?? '',
                                            ),
                                        status: defaultRosterStatus,
                                    }),
                                );
                            }}
                        >
                            {({ errors, processing }) => (
                                <>
                                    <DriverRosterFields
                                        value={rosterDraft}
                                        includeHiddenInputs
                                        showBranch
                                        rosterStatuses={rosterStatuses}
                                        errors={errors}
                                        branches={activeAssignments.map(
                                            (assignment) => ({
                                                id: assignment.branch_id,
                                                name:
                                                    assignment.branch_name ??
                                                    'Unknown branch',
                                                slug:
                                                    assignment.branch_slug ??
                                                    '',
                                            }),
                                        )}
                                        onChange={(patch) =>
                                            setRosterDraft((current) => ({
                                                ...current,
                                                ...patch,
                                            }))
                                        }
                                        statusTrailing={
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
                                        }
                                    />
                                </>
                            )}
                        </Form>

                        {driver.rosters.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No roster shifts yet.
                            </p>
                        ) : (
                            <div className="flex flex-col">
                                {driver.rosters.map((roster, index) => (
                                    <div key={roster.id}>
                                        {index > 0 ? (
                                            <Separator className="my-4" />
                                        ) : null}
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {roster.branch_name} ·{' '}
                                                    {formatRosterPeriodLine(
                                                        roster,
                                                    )}
                                                </p>
                                                <p className="text-muted-foreground text-sm">
                                                    {roster.status_label}
                                                </p>
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
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
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

PlatformDriversShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Drivers', href: '/platform/drivers' },
        { title: 'Show', href: '#' },
    ],
};
