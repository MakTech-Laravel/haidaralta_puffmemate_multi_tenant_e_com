import { Head, Link, setLayoutProps } from '@inertiajs/react';
import AreaController from '@/actions/App/Http/Controllers/Branch/AreaController';
import {
    BranchAreaDetail,
    type BranchAreaDetailData,
} from '@/components/admin/branch-area-detail';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

export default function BranchAreasShow({
    branch,
    area,
}: {
    branch: Branch;
    area: BranchAreaDetailData;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Areas',
                href: AreaController.index.url(branch.slug),
            },
            {
                title: area.name,
                href: AreaController.show.url([branch.slug, area.id]),
            },
        ],
    });

    return (
        <>
            <Head title={area.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <BranchAreaDetail
                    area={area}
                    branchName={branch.name}
                    actions={
                        <>
                            <Button asChild>
                                <Link
                                    href={AreaController.edit.url([
                                        branch.slug,
                                        area.id,
                                    ])}
                                >
                                    Edit
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link
                                    href={AreaController.index.url(branch.slug)}
                                >
                                    Back
                                </Link>
                            </Button>
                        </>
                    }
                />
            </div>
        </>
    );
}
