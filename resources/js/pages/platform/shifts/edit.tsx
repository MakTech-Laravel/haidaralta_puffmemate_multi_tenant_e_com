import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    BranchOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';

export default function PlatformShiftsEdit({
    workspace = 'platform',
    template,
    branches = [],
    selectedBranchId = null,
}: {
    workspace?: ShiftWorkspace;
    template: ShiftTemplateOption;
    branches?: BranchOption[];
    selectedBranchId?: number | null;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(
        selectedBranchId ?? template.branch_id,
    );

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url(branchQuery) },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${template.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Shift"
                    title={`Edit ${template.name}`}
                    description="Update the shift times. Overnight is inferred from the times."
                />
                <ShiftTemplateForm
                    action={routes.templates.update.form(template.id)}
                    branches={branches}
                    lockOwner
                    showBranchSelect={false}
                    defaults={template}
                    cancelHref={routes.templates.index.url(branchQuery)}
                />
            </div>
        </>
    );
}
