import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type { BranchOption } from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftPeriodsCreate({
    workspace = 'platform',
    branches = [],
    defaultOwnerType,
}: {
    workspace?: ShiftWorkspace;
    branches?: BranchOption[];
    defaultOwnerType: string;
}) {
    const routes = platformShiftWorkspace(workspace);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url() },
            { title: 'New period', href: routes.periods.create.url() },
        ],
    });

    return (
        <>
            <Head title="Create roster period" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create roster period"
                    description="A named date range. Assignments must stay inside these dates."
                />
                <ShiftPeriodForm
                    action={routes.periods.store.form()}
                    branches={branches}
                    lockOwner
                    showBranchSelect={routes.showBranchSelect}
                    defaults={{ owner_type: defaultOwnerType }}
                    cancelHref={routes.templates.index.url()}
                    submitLabel="Create period"
                />
            </div>
        </>
    );
}
