import { Head } from '@inertiajs/react';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import type {
    BranchOption,
    Option,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftsEdit({
    template,
    ownerTypes,
    branches,
}: {
    template: ShiftTemplateOption;
    ownerTypes: Option[];
    branches: BranchOption[];
}) {
    return (
        <>
            <Head title={`Edit ${template.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${template.name}`}
                    description="Update the duty window. Overnight is inferred from the times."
                />
                <ShiftTemplateForm
                    action={ShiftTemplateController.update.form(template.id)}
                    ownerTypes={ownerTypes}
                    branches={branches}
                    lockOwner
                    defaults={template}
                    cancelHref={ShiftTemplateController.index.url()}
                />
            </div>
        </>
    );
}

PlatformShiftsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Shifts', href: '/platform/shifts' },
        { title: 'Edit', href: '#' },
    ],
};
