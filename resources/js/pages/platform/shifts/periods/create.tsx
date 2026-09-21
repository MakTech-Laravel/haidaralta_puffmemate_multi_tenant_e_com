import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type { BranchOption } from '@/components/admin/workforce-types';

export default function PlatformShiftPeriodsCreate({
    workspace = 'platform',
    branches = [],
    selectedBranchId = null,
    defaultOwnerType,
}: {
    workspace?: ShiftWorkspace;
    branches?: BranchOption[];
    selectedBranchId?: number | null;
    defaultOwnerType: string;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const lockBranch =
        workspace === 'branch' && Boolean(selectedBranchId);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
            {
                title: 'New period',
                href: routes.periods.create.url(branchQuery),
            },
        ],
    });

    return (
        <>
            <Head title="Create roster period" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Roster"
                    title="Create roster period"
                    description="A named date range. Assignments must stay inside these dates."
                />
                <ShiftPeriodForm
                    action={routes.periods.store.form()}
                    branches={branches}
                    lockOwner
                    showBranchSelect={!lockBranch && routes.showBranchSelect}
                    defaults={{
                        owner_type: defaultOwnerType,
                        branch_id: selectedBranchId,
                    }}
                    cancelHref={routes.templates.index.url(branchQuery)}
                    submitLabel="Create period"
                />
            </div>
        </>
    );
}
