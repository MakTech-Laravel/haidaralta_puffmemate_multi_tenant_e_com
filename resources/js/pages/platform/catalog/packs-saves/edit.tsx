import { Head, setLayoutProps } from '@inertiajs/react';
import PacksSavesController from '@/actions/App/Http/Controllers/Platform/PacksSavesController';
import {
    BranchPackForm,
    type PackFormDefaults,
    type PackProductOption,
} from '@/components/branch/branch-pack-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function PlatformPacksSavesEdit({
    branch,
    pack,
    availableProducts,
}: {
    branch: Branch;
    pack: PackFormDefaults & { id: number };
    availableProducts: PackProductOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Packs & Saves',
                href: PacksSavesController.index.url(),
            },
            {
                title: pack.name ?? 'Pack',
                href: PacksSavesController.show.url(pack.id),
            },
            {
                title: 'Edit',
                href: PacksSavesController.edit.url(pack.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${pack.name ?? 'pack'}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${pack.name ?? 'pack'}`}
                    description={`Update this ${branch.name} pack’s offer, media, and how many packs you will sell`}
                />

                <BranchPackForm
                    action={PacksSavesController.update.form(pack.id)}
                    availableProducts={availableProducts}
                    cancelHref={PacksSavesController.show.url(pack.id)}
                    defaults={pack}
                    canHighlight
                    isEdit
                />
            </div>
        </>
    );
}
