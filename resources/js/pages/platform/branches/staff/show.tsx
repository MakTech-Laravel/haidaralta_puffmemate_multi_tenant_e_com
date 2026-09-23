import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    StaffMemberProfile,
    type StaffMemberProfileData,
} from '@/components/admin/staff-member-profile';
import { filteredBranchStaffUrl } from '@/lib/branch-people-query';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

export default function PlatformBranchScopedStaffShow({
    branch,
    staffMember,
}: {
    branch: Branch;
    staffMember: StaffMemberProfileData;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={staffMember.name} />

            <StaffMemberProfile
                staffMember={staffMember}
                backHref={filteredBranchStaffUrl(branch.id)}
                backLabel="All staff"
                editHref={BranchScopedStaffController.edit.url([
                    branch.slug,
                    staffMember.id,
                ])}
                onDelete={() => setConfirmOpen(true)}
            />

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete “${staffMember.name}” permanently?`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(
                        BranchScopedStaffController.destroy.url([
                            branch.slug,
                            staffMember.id,
                        ]),
                        {
                            preserveScroll: true,
                            onFinish: () => setProcessing(false),
                        },
                    );
                }}
            />
        </>
    );
}

PlatformBranchScopedStaffShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
        { title: 'Show', href: '#' },
    ],
};
