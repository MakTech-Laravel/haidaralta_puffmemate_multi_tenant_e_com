import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import type { ShiftTemplateOption } from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsEdit({
    branch,
    template,
}: {
    branch: Branch;
    template: ShiftTemplateOption;
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
            { title: `Edit ${template.name}`, href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${template.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${template.name}`}
                    description={`Update this branch-owned duty window`}
                />
                <ShiftTemplateForm
                    action={ShiftTemplateController.update.form({
                        branch: branch.slug,
                        shift_template: template.id,
                    })}
                    lockOwner
                    defaults={template}
                    cancelHref={ShiftTemplateController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
