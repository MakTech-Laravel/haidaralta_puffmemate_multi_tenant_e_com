import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type AreaOption = {
    id: number;
    name: string;
    branch_names: string[];
};

export default function PlatformBranchesCreate({
    areaOptions = [],
}: {
    areaOptions?: AreaOption[];
}) {
    return (
        <>
            <Head title="Add branch" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add branch"
                    description="Set up the location and the first person who will manage it"
                />

                <Alert>
                    <AlertTitle>One form, ready to go</AlertTitle>
                    <AlertDescription>
                        Fill in the branch details and create the first admin
                        account. You can add more staff later from the branch
                        staff screen.
                    </AlertDescription>
                </Alert>

                <BranchForm
                    action={BranchController.store.form()}
                    cancelHref={BranchController.index.url()}
                    includeSuperAdmin
                    areaOptions={areaOptions}
                    submitLabel="Create branch"
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
