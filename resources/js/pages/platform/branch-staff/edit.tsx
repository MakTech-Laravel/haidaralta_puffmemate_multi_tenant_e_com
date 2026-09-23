import { Head } from '@inertiajs/react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

type RoleOption = {
    value: string;
    label: string;
};

type SelectedBranch = {
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
    avatar: string | null;
    avatar_url: string | null;
};

export default function PlatformBranchStaffEdit({
    staffMember,
    roles,
    selectedBranch,
    lockedSuperAdmin,
}: {
    staffMember: StaffMember;
    roles: RoleOption[];
    selectedBranch: SelectedBranch;
    lockedSuperAdmin: boolean;
}) {
    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description="Update branch staff details and roles"
                />

                <StaffForm
                    action={BranchStaffController.update.form(staffMember.id)}
                    isEdit
                    roles={roles}
                    selectedBranch={selectedBranch}
                    branchLocked
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: staffMember.name,
                        phone: staffMember.phone,
                        email: staffMember.email,
                        roles: staffMember.roles,
                        is_active: staffMember.is_active,
                        branch_id: staffMember.branch_id,
                        avatar: staffMember.avatar,
                        avatar_url: staffMember.avatar_url,
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
