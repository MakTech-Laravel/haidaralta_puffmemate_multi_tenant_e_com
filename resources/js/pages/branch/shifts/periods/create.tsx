import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Branch/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftPeriodForm } from '@/components/admin/shift-period-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftPeriodsCreate({
    branch,
    defaultOwnerType,
}: {
    branch: Branch;
    defaultOwnerType: string;
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
            {
                title: 'New period',
                href: ShiftRosterPeriodController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Create roster period" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create roster period"
                    description={`A named date range for ${branch.name}`}
                />
                <ShiftPeriodForm
                    action={ShiftRosterPeriodController.store.form(branch.slug)}
                    lockOwner
                    defaults={{
                        owner_type: defaultOwnerType,
                        branch_id: branch.id,
                    }}
                    cancelHref={ShiftTemplateController.index.url(branch.slug)}
                    submitLabel="Create period"
                />
            </div>
        </>
    );
}
