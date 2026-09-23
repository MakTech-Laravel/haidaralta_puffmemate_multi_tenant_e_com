import { Head } from '@inertiajs/react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

export default function PlatformBranchStaffCreate() {
    return (
        <>
            <Head title="Add branch staff" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add branch staff"
                    description="Create a staff account for any branch"
                />

                <StaffForm
                    action={BranchStaffController.store.form()}
                    branchOptionsUrl={BranchStaffController.branchOptions.url()}
                    roleOptionsUrl={BranchStaffController.roleOptions.url()}
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
