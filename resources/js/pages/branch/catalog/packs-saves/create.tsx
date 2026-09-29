import { Head, setLayoutProps } from '@inertiajs/react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
import {
    BranchPackForm,
    type PackProductOption,
} from '@/components/branch/branch-pack-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchPacksSavesCreate({
    branch,
    availableProducts,
}: {
    branch: Branch;
    availableProducts: PackProductOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(branch.slug),
            },
            {
                title: 'Create pack',
                href: PacksSavesController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Create pack" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create pack"
                    description={`Pick ${branch.name} stock products, then set amount off, percent, or a pack price`}
                />

                <BranchPackForm
                    action={PacksSavesController.store.form(branch.slug)}
                    availableProducts={availableProducts}
                    cancelHref={PacksSavesController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
