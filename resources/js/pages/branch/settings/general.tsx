import { Head } from '@inertiajs/react';
import GeneralSettingsController from '@/actions/App/Http/Controllers/Branch/GeneralSettingsController';
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
    delivery_radius_km: string | number | null;
    opening_time: string | null;
    closing_time: string | null;
    status: boolean;
};

export default function BranchSettingsGeneral({ branch }: { branch: Branch }) {
    return (
        <>
            <Head title="General" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="General"
                    description={`Update ${branch.name} details`}
                />

                <BranchForm
                    action={GeneralSettingsController.update.form(branch.slug)}
                    isEdit
                    hideIdentityFields
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
                        delivery_radius_km: branch.delivery_radius_km,
                        opening_time: branch.opening_time,
                        closing_time: branch.closing_time,
                        status: branch.status,
                    }}
                    cancelHref={`/branch/${branch.slug}/dashboard`}
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}

BranchSettingsGeneral.layout = (props: { branch: Branch }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: `/branch/${props.branch.slug}/dashboard`,
        },
        {
            title: 'General',
            href: `/branch/${props.branch.slug}/settings/general`,
        },
    ],
});
