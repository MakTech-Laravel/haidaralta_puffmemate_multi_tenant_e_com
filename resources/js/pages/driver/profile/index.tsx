import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bike,
    Building2,
    CalendarDays,
    IdCard,
    Mail,
    MapPin,
    Phone,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { edit as editProfile, index as profileIndex } from '@/routes/driver/profile';

type DriverAssignment = {
    id: number;
    branch_id: number;
    branch_name: string | null;
    status: string | null;
    status_label: string | null;
};

type DriverRoster = {
    id: number;
    branch_name: string | null;
    start_time: string | null;
    end_time: string | null;
    days_label: string | null;
    period_label: string | null;
    status: string | null;
    status_label: string | null;
};

type DriverProfile = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    license_number: string | null;
    vehicle_type_label: string | null;
    effective_availability_label: string | null;
    address: string | null;
    avatar_url: string | null;
    vehicle_image_url: string | null;
    assignments: DriverAssignment[];
    rosters: DriverRoster[];
};

function Fact({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof Phone;
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex items-start gap-3">
            <span className="bg-muted mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg">
                <Icon className="text-muted-foreground size-4" aria-hidden />
            </span>
            <div className="min-w-0 space-y-0.5">
                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                    {label}
                </p>
                <p className="text-foreground text-sm font-medium wrap-break-word">
                    {value}
                </p>
            </div>
        </div>
    );
}

export default function DriverProfileIndex({
    driver,
}: {
    driver: DriverProfile;
}) {
    const getInitials = useInitials();
    const singleActiveBranch = usePage().props.drivers.single_active_branch;
    const activeAssignments = driver.assignments.filter(
        (assignment) => assignment.status === 'active',
    );
    const scheduledRosters = driver.rosters.filter(
        (roster) => roster.status === 'scheduled',
    );

    return (
        <>
            <Head title="Profile" />

            <div className="flex flex-col gap-5">
                <section className="bg-accent flex flex-col gap-4 rounded-xl p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 items-center gap-3">
                        <Avatar className="size-16">
                            {driver.avatar_url ? (
                                <AvatarImage
                                    src={driver.avatar_url}
                                    alt={driver.name}
                                />
                            ) : null}
                            <AvatarFallback className="text-base">
                                {getInitials(driver.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 space-y-1.5">
                            <h1 className="text-foreground truncate text-xl font-bold tracking-tight sm:text-2xl">
                                {driver.name}
                            </h1>
                            <div className="flex flex-wrap items-center gap-1.5">
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
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        className="h-12 w-full sm:w-auto"
                        asChild
                    >
                        <Link href={editProfile.url()} prefetch>
                            Edit details
                        </Link>
                    </Button>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Contact
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Fact
                            icon={Phone}
                            label="Phone"
                            value={
                                <a
                                    href={`tel:${driver.phone}`}
                                    className="hover:text-primary"
                                >
                                    {driver.phone}
                                </a>
                            }
                        />
                        <Fact
                            icon={Mail}
                            label="Email"
                            value={driver.email ?? 'Not on file'}
                        />
                        <Fact
                            icon={IdCard}
                            label="License"
                            value={driver.license_number ?? 'Not on file'}
                        />
                        <Fact
                            icon={MapPin}
                            label="Address"
                            value={driver.address ?? 'Not on file'}
                        />
                    </div>
                </section>

                <section className="border-border space-y-4 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Vehicle
                    </h2>
                    {driver.vehicle_image_url ? (
                        <img
                            src={driver.vehicle_image_url}
                            alt={
                                driver.vehicle_type_label
                                    ? `${driver.vehicle_type_label} assigned to you`
                                    : 'Assigned vehicle'
                            }
                            className="border-border h-40 w-full rounded-lg border object-cover sm:h-52"
                        />
                    ) : (
                        <div className="bg-muted text-muted-foreground flex h-28 items-center justify-center gap-2 rounded-lg text-sm">
                            <Bike className="size-4" aria-hidden />
                            No vehicle photo yet
                        </div>
                    )}
                    <p className="text-foreground text-sm font-medium">
                        {driver.vehicle_type_label ?? 'Vehicle not set'}
                    </p>
                </section>

                <section className="border-border space-y-3 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        {singleActiveBranch
                            ? 'Assigned branch'
                            : 'Assigned branches'}
                    </h2>
                    {activeAssignments.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {singleActiveBranch
                                ? 'No active branch assignment yet.'
                                : 'No active branch assignments yet.'}
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {activeAssignments.map((assignment) => (
                                <li
                                    key={assignment.id}
                                    className="bg-accent flex items-center gap-3 rounded-lg px-3 py-2.5"
                                >
                                    <Building2
                                        className="text-muted-foreground size-4 shrink-0"
                                        aria-hidden
                                    />
                                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                        {assignment.branch_name ?? 'Branch'}
                                    </span>
                                    <Badge variant="secondary">
                                        {assignment.status_label ?? 'Active'}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="border-border space-y-3 rounded-xl border p-4">
                    <h2 className="text-foreground text-base font-semibold">
                        Roster
                    </h2>
                    {scheduledRosters.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No scheduled shifts on file. The desk will add them
                            when you are rostered.
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-2">
                            {scheduledRosters.map((roster) => (
                                <li
                                    key={roster.id}
                                    className="bg-accent space-y-1 rounded-lg px-3 py-2.5"
                                >
                                    <div className="flex items-center gap-2">
                                        <CalendarDays
                                            className="text-muted-foreground size-4 shrink-0"
                                            aria-hidden
                                        />
                                        <p className="min-w-0 flex-1 truncate text-sm font-medium">
                                            {roster.branch_name ?? 'Branch'}
                                        </p>
                                        <Badge variant="outline">
                                            {roster.status_label ?? 'Scheduled'}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground pl-6 text-sm">
                                        {[
                                            roster.period_label,
                                            roster.days_label,
                                            roster.start_time && roster.end_time
                                                ? `${roster.start_time}–${roster.end_time}`
                                                : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

DriverProfileIndex.layout = {
    breadcrumbs: [
        {
            title: 'Profile',
            href: profileIndex.url(),
        },
    ],
};
