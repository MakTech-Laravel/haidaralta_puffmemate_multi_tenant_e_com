import { Head, setLayoutProps } from '@inertiajs/react';
import BranchAreasController from '@/actions/App/Http/Controllers/Platform/BranchAreasController';
import { BranchAreaForm } from '@/components/admin/branch-area-form';
import Heading from '@/components/heading';

export default function PlatformBranchAreasCreate() {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Areas',
                href: BranchAreasController.index.url(),
            },
            {
                title: 'Create',
                href: BranchAreasController.create.url(),
            },
        ],
    });

    return (
        <>
            <Head title="Add area" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add area"
                    description="Define a named delivery zone with an address pin for any branch."
                />

                <BranchAreaForm
                    action={BranchAreasController.store.form()}
                    branchOptionsUrl={BranchAreasController.branchOptions.url()}
                    cancelHref={BranchAreasController.index.url()}
                />
            </div>
        </>
    );
}
