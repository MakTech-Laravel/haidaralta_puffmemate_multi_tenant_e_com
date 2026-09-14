import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
    logo: string | null;
    logo_url: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    opening_time: string | null;
    closing_time: string | null;
    status: boolean;
};

export default function PlatformBranchesEdit({ branch }: { branch: Branch }) {
    return (
        <>
            <Head title={`Edit ${branch.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${branch.name}`}
                    description="Update branch details"
                />

                {!branch.status ? (
                    <Alert>
                        <AlertTitle>Branch portal inaccessible</AlertTitle>
                        <AlertDescription>
                            This branch is inactive. Staff cannot open the
                            branch portal until you reactivate it.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <BranchForm
                    action={BranchController.update.form(branch.slug)}
                    isEdit
                    defaults={{
                        name: branch.name,
                        slug: branch.slug,
                        code: branch.code,
                        logo: branch.logo,
                        logo_url: branch.logo_url,
                        phone: branch.phone,
                        email: branch.email,
                        address: branch.address,
                        latitude: branch.latitude,
                        longitude: branch.longitude,
                        opening_time: branch.opening_time,
                        closing_time: branch.closing_time,
                        status: branch.status,
                    }}
                    cancelHref={BranchController.show.url(branch.slug)}
                />
            </div>
        </>
    );
}

PlatformBranchesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Edit', href: '#' },
    ],
};
