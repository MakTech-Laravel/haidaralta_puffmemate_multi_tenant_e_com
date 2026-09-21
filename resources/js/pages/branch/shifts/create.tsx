import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsCreate({
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
            { title: 'Create', href: ShiftTemplateController.create.url(branch.slug) },
        ],
    });
    return (
        <>
            <Head title="Create shift template" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create shift template"
                    description={`A duty window owned by ${branch.name}`}
                />
                <ShiftTemplateForm
                    action={ShiftTemplateController.store.form(branch.slug)}
                    lockOwner
                    defaults={{
                        owner_type: defaultOwnerType,
                        branch_id: branch.id,
                    }}
                    cancelHref={ShiftTemplateController.index.url(branch.slug)}
                    submitLabel="Create template"
                />
            </div>
        </>
    );
}

