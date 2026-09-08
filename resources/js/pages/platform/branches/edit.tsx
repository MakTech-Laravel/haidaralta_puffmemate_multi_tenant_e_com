import { Head } from '@inertiajs/react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { BranchForm } from '@/components/admin/branch-form';
import Heading from '@/components/heading';

type Branch = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    icon_url: string | null;
    address: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    is_active: boolean;
};

export default function PlatformBranchesEdit({ branch }: { branch: Branch }) {
    return (
        <>
            <Head title={`Edit ${branch.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
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
                        icon: branch.icon,
                        icon_url: branch.icon_url,
                        address: branch.address,
                        latitude: branch.latitude,
                        longitude: branch.longitude,
                        is_active: branch.is_active,
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
