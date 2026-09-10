import { Head } from '@inertiajs/react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

type BranchOption = {
    value: string;
    label: string;
};

type RoleOption = {
    value: string;
    label: string;
};

export default function PlatformBranchStaffCreate({
    branches,
    rolesByBranch,
}: {
    branches: BranchOption[];
    rolesByBranch: Record<string, RoleOption[]>;
}) {
    return (
        <>
            <Head title="Add branch staff" />

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <Heading
                    title="Add branch staff"
                    description="Create a staff account for any branch"
                />

                <StaffForm
                    action={BranchStaffController.store.form()}
                    branches={branches}
                    rolesByBranch={rolesByBranch}
                    cancelHref={BranchStaffController.index.url()}
                    submitLabel="Create staff"
                />
            </div>
        </>
    );
}

PlatformBranchStaffCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
        { title: 'Create', href: '/platform/branch-staff/create' },
    ],
};
