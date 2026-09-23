import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    StaffMemberProfile,
    type StaffMemberProfileData,
} from '@/components/admin/staff-member-profile';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

export default function BranchStaffShow({
    branch,
    staffMember,
}: {
    branch: Branch;
    staffMember: StaffMemberProfileData;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Staff', href: `/branch/${branch.slug}/staff` },
            { title: 'Show', href: '#' },
        ],
    });

    return (
        <>
            <Head title={staffMember.name} />

            <StaffMemberProfile
                staffMember={staffMember}
                backHref={StaffController.index.url(branch.slug)}
                editHref={StaffController.edit.url({
                    branch: branch.slug,
                    user: staffMember.id,
                })}
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
                        StaffController.destroy.url({
                            branch: branch.slug,
                            user: staffMember.id,
                        }),
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
