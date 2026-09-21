import { Head } from '@inertiajs/react';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Platform/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import type {
    BranchOption,
    Option,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftPeriodsCreate({
    ownerTypes,
    branches,
    defaultOwnerType,
}: {
    ownerTypes: Option[];
    branches: BranchOption[];
    defaultOwnerType: string;
}) {
    return (
        <>
            <Head title="Create roster period" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create roster period"
                    description="A named date range. Assignments must stay inside these dates."
                />
                <ShiftPeriodForm
                    action={ShiftRosterPeriodController.store.form()}
                    ownerTypes={ownerTypes}
                    branches={branches}
                    defaults={{ owner_type: defaultOwnerType }}
                    cancelHref={ShiftTemplateController.index.url()}
                    submitLabel="Create period"
                />
            </div>
        </>
    );
}

PlatformShiftPeriodsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Shifts', href: '/platform/shifts' },
        { title: 'New period', href: '/platform/shift-periods/create' },
    ],
};
