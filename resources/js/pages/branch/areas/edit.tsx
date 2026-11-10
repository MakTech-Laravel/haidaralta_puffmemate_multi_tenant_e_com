import { Head, setLayoutProps } from '@inertiajs/react';
import AreaController from '@/actions/App/Http/Controllers/Branch/AreaController';
import { BranchAreaForm } from '@/components/admin/branch-area-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

type Area = {
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    polygon?: { lat: number; lng: number }[] | null;
    city: string;
    state: string;
    postal_code: string | null;
    notes: string | null;
    is_active: boolean;
};

export default function BranchAreasEdit({
    branch,
    area,
}: {
    branch: Branch;
    area: Area;
}) {
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
            {
                title: area.name,
                href: AreaController.show.url([branch.slug, area.id]),
            },
            {
                title: 'Edit',
                href: AreaController.edit.url([branch.slug, area.id]),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${area.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Edit area"
                    description={`Update the delivery zone “${area.name}” for ${branch.name}.`}
                />

                <BranchAreaForm
                    action={AreaController.update.form([branch.slug, area.id])}
                    isEdit
                    defaults={area}
                    cancelHref={AreaController.show.url([
                        branch.slug,
                        area.id,
                    ])}
                />
            </div>
        </>
    );
}
