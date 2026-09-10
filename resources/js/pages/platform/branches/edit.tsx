import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';

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

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <Heading
                    title={`Edit ${branch.name}`}
                    description="Update branch details"
                />

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
