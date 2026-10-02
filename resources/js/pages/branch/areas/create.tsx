import { Head, setLayoutProps } from '@inertiajs/react';
import AreaController from '@/actions/App/Http/Controllers/Branch/AreaController';
import { BranchAreaForm } from '@/components/admin/branch-area-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchAreasCreate({ branch }: { branch: Branch }) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Areas',
                href: AreaController.index.url(branch.slug),
            },
            { title: 'Create', href: AreaController.create.url(branch.slug) },
        ],
    });

    return (
        <>
            <Head title="Add area" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add area"
                    description={`Define a named delivery zone with an address pin for ${branch.name}.`}
                />

                <BranchAreaForm
                    action={AreaController.store.form(branch.slug)}
                    cancelHref={AreaController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
