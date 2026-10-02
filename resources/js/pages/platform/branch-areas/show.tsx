import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import BranchAreasController from '@/actions/App/Http/Controllers/Platform/BranchAreasController';
import {
    BranchAreaDetail,
    type BranchAreaDetailData,
} from '@/components/admin/branch-area-detail';
import { TransferBranchAreaSheet } from '@/components/admin/transfer-branch-area-sheet';
import { Button } from '@/components/ui/button';
import { filteredBranchAreasUrl } from '@/lib/branch-people-query';

type Branch = { id: number; name: string; slug: string };

export default function PlatformBranchAreasShow({
    branch,
    area,
    canManage,
}: {
    branch: Branch | null;
    area: BranchAreaDetailData;
    canManage: boolean;
}) {
    const [transferOpen, setTransferOpen] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Areas',
                href: BranchAreasController.index.url(),
            },
            {
                title: area.name,
                href: BranchAreasController.show.url(area.id),
            },
        ],
    });

    return (
        <>
            <Head title={area.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <BranchAreaDetail
                    area={area}
                    branchName={branch?.name}
                    actions={
                        <>
                            {canManage ? (
                                <>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setTransferOpen(true)}
                                    >
                                        Change branch
                                    </Button>
                                    <Button asChild>
                                        <Link
                                            href={BranchAreasController.edit.url(
                                                area.id,
                                            )}
                                        >
                                            Edit
                                        </Link>
                                    </Button>
                                </>
                            ) : null}
                            <Button variant="outline" asChild>
                                <Link
                                    href={
                                        branch
                                            ? filteredBranchAreasUrl(branch.id)
                                            : BranchAreasController.index.url()
                                    }
                                >
                                    Back
                                </Link>
                            </Button>
                        </>
                    }
                />
            </div>

            {canManage ? (
                <TransferBranchAreaSheet
                    open={transferOpen}
                    onOpenChange={setTransferOpen}
                    action={BranchAreasController.transfer.form(area.id)}
                    areaName={area.name}
                    currentBranchId={branch?.id}
                    currentBranchName={branch?.name}
                    branchOptionsUrl={BranchAreasController.branchOptions.url()}
                />
            ) : null}
        </>
    );
}
