import { Head, setLayoutProps } from '@inertiajs/react';
import BranchDriverController from '@/actions/App/Http/Controllers/Platform/BranchDriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type Branch = { id: number; name: string; slug: string };

export default function PlatformBranchDriversCreate({
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
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Drivers',
                href: BranchDriverController.index.url(branch.slug),
            },
            {
                title: 'Create',
                href: BranchDriverController.create.url(branch.slug),
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
                    action={BranchDriverController.store.form(branch.slug)}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
                    cancelHref={BranchDriverController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
