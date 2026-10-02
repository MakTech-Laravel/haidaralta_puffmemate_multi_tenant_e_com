import { Head, setLayoutProps } from '@inertiajs/react';
import BranchAreaController from '@/actions/App/Http/Controllers/Platform/BranchAreaController';
import BranchAreasController from '@/actions/App/Http/Controllers/Platform/BranchAreasController';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchAreaForm } from '@/components/admin/branch-area-form';
import Heading from '@/components/heading';
import { filteredBranchAreasUrl } from '@/lib/branch-people-query';

type Branch = { id: number; name: string; slug: string };

export default function PlatformBranchAreasCreate({
    branch,
}: {
    branch: Branch;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Areas',
                href: BranchAreasController.index.url(),
            },
            {
                title: branch.name,
                href: BranchController.show.url(branch.slug),
            },
            {
                title: 'Create',
                href: BranchAreaController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Add area" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add area"
                    description={`Create a delivery zone for ${branch.name}`}
                />

                <BranchAreaForm
                    action={BranchAreaController.store.form(branch.slug)}
                    cancelHref={filteredBranchAreasUrl(branch.id)}
                />
            </div>
        </>
    );
}
