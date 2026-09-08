import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';

export default function PlatformBranchesCreate() {
    return (
        <>
            <Head title="Add branch" />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title="Add branch"
                    description="Create a branch and its required super-admin"
                />

                <BranchForm
                    action={BranchController.store.form()}
                    cancelHref={BranchController.index.url()}
                />
            </div>
        </>
    );
}

PlatformBranchesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Create', href: '/platform/branches/create' },
    ],
};
