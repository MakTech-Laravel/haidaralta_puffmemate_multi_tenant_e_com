import { Head, Link, setLayoutProps } from '@inertiajs/react';
import type {
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
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

export default function PlatformShiftsIndex({
    workspace = 'platform',
    templates,
    periods,
}: {
    workspace?: ShiftWorkspace;
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
}) {
    const routes = platformShiftWorkspace(workspace);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url() },
        ],
    });

    return (
        <>
            <Head title={routes.title} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={routes.title}
                        description={routes.description}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={routes.periods.create.url()}>
                                New period
                            </Link>
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link href={routes.assignments.create.url()}>
                                Assign to shift
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={routes.templates.create.url()}>
                                New template
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Templates</CardTitle>
                            <CardDescription>
                                Named start and end times, including overnight
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {templates.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No shift templates yet.
                                </p>
                            ) : (
                                templates.map((template) => (
                                    <div
                                        key={template.id}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-3 py-2.5"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {template.name} ·{' '}
                                                {template.start_time}–
                                                {template.end_time}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                {template.branch_name
                                                    ? template.branch_name
                                                    : template.owner_type_label}
                                                {template.is_overnight
                                                    ? ' · Overnight'
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={
                                                    template.is_active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {template.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={routes.templates.edit.url(
                                                        template.id,
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Roster periods</CardTitle>
                            <CardDescription>
                                Named date ranges assignments must stay inside
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {periods.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    No roster periods yet.
                                </p>
                            ) : (
                                periods.map((period) => (
                                    <div
                                        key={period.id}
                                        className="flex flex-wrap items-center justify-between gap-3 rounded-xl border px-3 py-2.5"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">
                                                {period.name}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                {period.start_date} –{' '}
                                                {period.end_date}
                                                {period.branch_name
                                                    ? ` · ${period.branch_name}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={routes.periods.edit.url(
                                                    period.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
