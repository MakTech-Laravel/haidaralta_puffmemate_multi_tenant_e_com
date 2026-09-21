import { Head } from '@inertiajs/react';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Platform/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import type {
    BranchOption,
    Option,
    ShiftPeriodOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftPeriodsEdit({
    period,
    ownerTypes,
    branches,
}: {
    period: ShiftPeriodOption;
    ownerTypes: Option[];
    branches: BranchOption[];
}) {
    return (
        <>
            <Head title={`Edit ${period.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${period.name}`}
                    description="Update the named date range assignments must stay inside."
                />
                <ShiftPeriodForm
                    action={ShiftRosterPeriodController.update.form(period.id)}
                    ownerTypes={ownerTypes}
                    branches={branches}
                    lockOwner
                    defaults={period}
                    cancelHref={ShiftTemplateController.index.url()}
                />
            </div>
        </>
    );
}

PlatformShiftPeriodsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Shifts', href: '/platform/shifts' },
        { title: 'Edit period', href: '#' },
    ],
};
