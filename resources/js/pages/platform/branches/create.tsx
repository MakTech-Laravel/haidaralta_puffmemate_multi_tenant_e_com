import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';

export default function PlatformBranchesCreate() {
    return (
        <>
            <Head title="Add branch" />

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge>Step 1 of 2</Badge>
                        <Badge variant="outline">Branch details</Badge>
                    </div>
                    <Heading
                        title="Add branch"
                        description="Enter branch details, then create a super-admin"
                    />
                </div>

                <Alert>
                    <AlertTitle>Next step</AlertTitle>
                    <AlertDescription>
                        Saving creates the branch and opens the super-admin
                        form. You can skip super-admin and finish later from the
                        branch page.
                    </AlertDescription>
                </Alert>

                <BranchForm
                    action={BranchController.store.form()}
                    cancelHref={BranchController.index.url()}
                    submitLabel="Next"
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
