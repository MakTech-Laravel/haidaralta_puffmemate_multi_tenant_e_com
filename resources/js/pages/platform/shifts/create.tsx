import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type { BranchOption } from '@/components/admin/workforce-types';
import Heading from '@/components/heading';

export default function PlatformShiftsCreate({
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
            { title: 'Create', href: routes.templates.create.url() },
        ],
    });

    return (
        <>
            <Head title="Create shift template" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create shift template"
                    description={routes.description}
                />
                <ShiftTemplateForm
                    action={routes.templates.store.form()}
                    branches={branches}
                    lockOwner
                    showBranchSelect={routes.showBranchSelect}
                    defaults={{ owner_type: defaultOwnerType }}
                    cancelHref={routes.templates.index.url()}
                    submitLabel="Create template"
                />
            </div>
        </>
    );
}
