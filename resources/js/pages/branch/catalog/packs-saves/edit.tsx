import { Head, setLayoutProps } from '@inertiajs/react';
import PacksSavesController from '@/actions/App/Http/Controllers/Branch/PacksSavesController';
import {
    BranchPackForm,
    type PackFormDefaults,
    type PackProductOption,
} from '@/components/branch/branch-pack-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchPacksSavesEdit({
    branch,
    pack,
    availableProducts,
}: {
    branch: Branch;
    pack: PackFormDefaults & { slug: string };
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
                title: pack.name ?? 'Pack',
                href: PacksSavesController.show.url({
                    branch: branch.slug,
                    pack: pack.slug,
                }),
            },
            {
                title: 'Edit',
                href: PacksSavesController.edit.url({
                    branch: branch.slug,
                    pack: pack.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${pack.name ?? 'pack'}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${pack.name ?? 'pack'}`}
                    description="Update this pack’s offer, media, and how many packs you will sell"
                />

                <BranchPackForm
                    action={PacksSavesController.update.form({
                        branch: branch.slug,
                        pack: pack.slug,
                    })}
                    availableProducts={availableProducts}
                    cancelHref={PacksSavesController.show.url({
                        branch: branch.slug,
                        pack: pack.slug,
                    })}
                    defaults={pack}
                    isEdit
                />
            </div>
        </>
    );
}
