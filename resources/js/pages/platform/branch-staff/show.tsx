import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import TransferController from '@/actions/App/Http/Controllers/Platform/TransferController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    StaffMemberProfile,
    type StaffMemberProfileData,
} from '@/components/admin/staff-member-profile';
import { TransferEmployeeSheet } from '@/components/admin/transfer-employee-sheet';
import { TransferTimeline } from '@/components/admin/workforce-timeline';
import type { TransferRow } from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

type StaffMember = StaffMemberProfileData & {
    transfers: TransferRow[];
};

export default function PlatformBranchStaffShow({
    staffMember,
}: {
    staffMember: StaffMember;
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [transferOpen, setTransferOpen] = useState(false);

    return (
        <>
            <Head title={staffMember.name} />

            <StaffMemberProfile
                staffMember={staffMember}
                backHref={BranchStaffController.index.url()}
                editHref={BranchStaffController.edit.url(staffMember.id)}
                onDelete={() => setConfirmOpen(true)}
                extraActions={
                    <Button
                        variant="outline"
                        onClick={() => setTransferOpen(true)}
                    >
                        Transfer
                    </Button>
                }
            >
                <section className="bg-card overflow-hidden rounded-2xl border">
                    <div className="px-5 py-4">
                        <h2 className="text-base font-semibold tracking-tight">
                            Transfer history
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Branch and role moves for this staff member
                        </p>
                    </div>
                    <Separator />
                    <div className="px-5 py-4">
                        <TransferTimeline
                            transfers={staffMember.transfers ?? []}
                        />
                    </div>
                </section>
            </StaffMemberProfile>

            <TransferEmployeeSheet
                open={transferOpen}
                onOpenChange={setTransferOpen}
                action={TransferController.storeStaff.form(staffMember.id)}
                subjectType="branch_staff"
                branchOptionsUrl={BranchStaffController.branchOptions.url()}
                roleOptionsUrl={BranchStaffController.roleOptions.url()}
                currentBranchId={staffMember.branch?.id}
                currentBranchName={staffMember.branch?.name}
                currentRoles={staffMember.roles}
            />

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                description={`Delete “${staffMember.name}” permanently?`}
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(
                        BranchStaffController.destroy.url(staffMember.id),
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

PlatformBranchStaffShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
        { title: 'Show', href: '#' },
    ],
};
