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

type StaffMember = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    branch_id: number;
    roles: string[];
};

export default function PlatformBranchStaffEdit({
    staffMember,
    branches,
    rolesByBranch,
    lockedSuperAdmin,
}: {
    staffMember: StaffMember;
    branches: BranchOption[];
    rolesByBranch: Record<string, RoleOption[]>;
    lockedSuperAdmin: boolean;
}) {
    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-2">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description="Update branch staff details and roles"
                />

                <StaffForm
                    action={BranchStaffController.update.form(staffMember.id)}
                    isEdit
                    branches={branches}
                    rolesByBranch={rolesByBranch}
                    branchLocked
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: staffMember.name,
                        phone: staffMember.phone,
                        email: staffMember.email,
                        roles: staffMember.roles,
                        is_active: staffMember.is_active,
                        branch_id: staffMember.branch_id,
                    }}
                    cancelHref={BranchStaffController.show.url(staffMember.id)}
                />
            </div>
        </>
    );
}

PlatformBranchStaffEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
        { title: 'Edit', href: '#' },
    ],
};
