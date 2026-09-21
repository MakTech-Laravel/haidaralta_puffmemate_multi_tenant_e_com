import { Head } from '@inertiajs/react';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import type {
    BranchOption,
    Option,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftsCreate({
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
            <Head title="Create shift template" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create shift template"
                    description="A reusable duty window. Overnight is inferred from the times."
                />
                <ShiftTemplateForm
                    action={ShiftTemplateController.store.form()}
                    ownerTypes={ownerTypes}
                    branches={branches}
                    defaults={{ owner_type: defaultOwnerType }}
                    cancelHref={ShiftTemplateController.index.url()}
                    submitLabel="Create template"
                />
            </div>
        </>
    );
}

PlatformShiftsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Shifts', href: '/platform/shifts' },
        { title: 'Create', href: '/platform/shifts/create' },
    ],
};
