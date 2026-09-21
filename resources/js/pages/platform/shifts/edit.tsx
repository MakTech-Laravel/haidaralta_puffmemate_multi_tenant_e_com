import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    BranchOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftsEdit({
    workspace = 'platform',
    template,
    branches = [],
}: {
    workspace?: ShiftWorkspace;
    template: ShiftTemplateOption;
    branches?: BranchOption[];
}) {
    const routes = platformShiftWorkspace(workspace);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url() },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${template.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${template.name}`}
                    description="Update the duty window. Overnight is inferred from the times."
                />
                <ShiftTemplateForm
                    action={routes.templates.update.form(template.id)}
                    branches={branches}
                    lockOwner
                    showBranchSelect={false}
                    defaults={template}
                    cancelHref={routes.templates.index.url()}
                />
            </div>
        </>
    );
}
