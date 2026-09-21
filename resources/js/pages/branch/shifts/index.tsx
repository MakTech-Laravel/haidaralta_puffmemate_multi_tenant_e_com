import { Head, Link, setLayoutProps } from '@inertiajs/react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Branch/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import type {
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
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

export default function BranchShiftsIndex({
    branch,
    templates,
    periods,
}: {
    branch: Branch;
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Shifts',
                href: ShiftTemplateController.index.url(branch.slug),
            },
        ],
    });
    return (
        <>
            <Head title="Shifts" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Shifts"
                        description={`Duty windows available at ${branch.name}`}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="secondary" asChild>
                            <Link
                                href={ShiftRosterPeriodController.create.url(
                                    branch.slug,
                                )}
                            >
                                New period
                            </Link>
                        </Button>
                        <Button variant="secondary" asChild>
                            <Link
                                href={ShiftAssignmentController.create.url(
                                    branch.slug,
                                )}
                            >
                                Assign to shift
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={ShiftTemplateController.create.url(
                                    branch.slug,
                                )}
                            >
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
                                Platform templates plus this branch’s own windows
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
                                                {template.owner_type_label}
                                                {template.is_overnight
                                                    ? ' · Overnight'
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary">
                                                {template.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                            {template.branch_id ===
                                            branch.id ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ShiftTemplateController.edit.url(
                                                            {
                                                                branch: branch.slug,
                                                                shift_template:
                                                                    template.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            ) : null}
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
                                Named date ranges for this branch
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
                                            </p>
                                        </div>
                                        {period.branch_id === branch.id ? (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={ShiftRosterPeriodController.edit.url(
                                                        {
                                                            branch: branch.slug,
                                                            shift_roster_period:
                                                                period.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                        ) : null}
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

