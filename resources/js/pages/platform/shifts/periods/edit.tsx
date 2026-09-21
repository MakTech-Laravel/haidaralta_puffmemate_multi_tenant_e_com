import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    BranchOption,
    ShiftPeriodOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftPeriodsEdit({
    workspace = 'platform',
    period,
    branches = [],
}: {
    workspace?: ShiftWorkspace;
    period: ShiftPeriodOption;
    branches?: BranchOption[];
}) {
    const routes = platformShiftWorkspace(workspace);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url() },
            { title: 'Edit period', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${period.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${period.name}`}
                    description="Update the named date range assignments must stay inside."
                />
                <ShiftPeriodForm
                    action={routes.periods.update.form(period.id)}
                    branches={branches}
                    lockOwner
                    showBranchSelect={false}
                    defaults={period}
                    cancelHref={routes.templates.index.url()}
                />
            </div>
        </>
    );
}
