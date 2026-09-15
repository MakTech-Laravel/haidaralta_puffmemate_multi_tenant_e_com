import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { IdCard, Mail, MapPin, Phone } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
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
    notes: string | null;
    address: string | null;
    avatar_url: string | null;
    vehicle_image_url: string | null;
    assignments: {
        id: number;
        status_label: string | null;
        branch_name: string | null;
    }[];
    rosters: {
        id: number;
        start_date: string | null;
        end_date: string | null;
        start_time: string | null;
        end_time: string | null;
        days_label: string | null;
        period_label: string | null;
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

export default function BranchDriversShow({
    branch,
    driver,
    rosterStatuses,
}: {
    branch: Branch;
    driver: Driver;
    rosterStatuses: Option[];
}) {
    const defaultRosterStatus =
        rosterStatuses.find((item) => item.value === 'scheduled')?.value ??
        rosterStatuses[0]?.value ??
        'scheduled';
    const [rosterDraft, setRosterDraft] = useState(() =>
        emptyRosterFields({
            branchId: String(branch.id),
            status: defaultRosterStatus,
        }),
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
                    <Button asChild>
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

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                            <CardDescription>
                                Vehicle and availability at {branch.name}
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
                        <CardTitle>Rosters at {branch.name}</CardTitle>
                        <CardDescription>
                            Shift duty times by date period
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Form
                            action={DriverController.storeRoster.url({
                                branch: branch.slug,
                                user: driver.id,
                            })}
                            method="post"
                            className="space-y-3 rounded-xl border p-3"
                            options={{ preserveScroll: true }}
                            onSuccess={() => {
                                setRosterDraft(
                                    emptyRosterFields({
                                        branchId: String(branch.id),
                                        status: defaultRosterStatus,
                                    }),
                                );
                            }}
                        >
                            {({ errors, processing }) => (
                                <DriverRosterFields
                                    value={rosterDraft}
                                    includeHiddenInputs
                                    rosterStatuses={rosterStatuses}
                                    errors={errors}
                                    onChange={(patch) =>
                                        setRosterDraft((current) => ({
                                            ...current,
                                            ...patch,
                                        }))
                                    }
                                    statusTrailing={
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing ? <Spinner /> : null}
                                            Add
                                        </Button>
                                    }
                                />
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
                                                                branch: branch.slug,
                                                                user: driver.id,
                                                                roster: roster.id,
                                                            },
                                                        ),
                                                        {
                                                            preserveScroll:
                                                                true,
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
        </>
    );
}
