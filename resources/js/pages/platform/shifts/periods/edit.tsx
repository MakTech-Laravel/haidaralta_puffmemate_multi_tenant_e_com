import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    BranchOption,
    ShiftPeriodOption,
} from '@/components/admin/workforce-types';

export default function PlatformShiftPeriodsEdit({
    workspace = 'platform',
    period,
    branches = [],
    selectedBranchId = null,
}: {
    workspace?: ShiftWorkspace;
    period: ShiftPeriodOption;
    branches?: BranchOption[];
    selectedBranchId?: number | null;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(
        selectedBranchId ?? period.branch_id,
    );

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url(branchQuery) },
            { title: 'Edit period', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${period.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Roster"
                    title={`Edit ${period.name}`}
                    description="Update the named date range assignments must stay inside."
                />
                <ShiftPeriodForm
                    action={routes.periods.update.form(period.id)}
                    branches={branches}
                    lockOwner
                    showBranchSelect={false}
                    defaults={period}
                    cancelHref={routes.templates.index.url(branchQuery)}
                />
            </div>
        </>
    );
}
