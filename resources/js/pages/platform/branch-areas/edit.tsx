import { Head, setLayoutProps } from '@inertiajs/react';
import BranchAreasController from '@/actions/App/Http/Controllers/Platform/BranchAreasController';
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

export default function PlatformBranchAreasEdit({
    branch,
    area,
}: {
    branch: Branch | null;
    area: Area;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: 'Areas',
                href: BranchAreasController.index.url(),
            },
            {
                title: area.name,
                href: BranchAreasController.show.url(area.id),
            },
            {
                title: 'Edit',
                href: BranchAreasController.edit.url(area.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${area.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Edit area"
                    description={
                        branch
                            ? `Update the delivery zone “${area.name}” for ${branch.name}.`
                            : `Update the delivery zone “${area.name}”.`
                    }
                />

                <BranchAreaForm
                    action={BranchAreasController.update.form(area.id)}
                    isEdit
                    defaults={area}
                    cancelHref={BranchAreasController.show.url(area.id)}
                />
            </div>
        </>
    );
}
