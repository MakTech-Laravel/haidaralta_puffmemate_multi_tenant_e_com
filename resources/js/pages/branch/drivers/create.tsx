import { Head, setLayoutProps } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type Branch = { id: number; name: string; slug: string };

export default function BranchDriversCreate({
    branch,
    vehicleTypes,
    availabilityStatuses,
}: {
    branch: Branch;
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Drivers',
                href: DriverController.index.url(branch.slug),
            },
            {
                title: 'Create',
                href: DriverController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Add driver" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add driver"
                    description={`Create a driver and assign them to ${branch.name}`}
                />

                <DriverForm
                    action={DriverController.store.form(branch.slug)}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
                    cancelHref={DriverController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
