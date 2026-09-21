import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Branch/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import type { ShiftPeriodOption } from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftPeriodsEdit({
    branch,
    period,
}: {
    branch: Branch;
    period: ShiftPeriodOption;
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
            { title: `Edit ${period.name}`, href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${period.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${period.name}`}
                    description="Update this branch-owned roster period"
                />
                <ShiftPeriodForm
                    action={ShiftRosterPeriodController.update.form({
                        branch: branch.slug,
                        shift_roster_period: period.id,
                    })}
                    lockOwner
                    defaults={period}
                    cancelHref={ShiftTemplateController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}