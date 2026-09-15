import { Head } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string; code?: string | null };

export default function PlatformDriversCreate({
    branches,
    vehicleTypes,
    availabilityStatuses,
    rosterStatuses,
}: {
    branches: BranchOption[];
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
    rosterStatuses: Option[];
}) {
    return (
        <>
            <Head title="Add driver" />

            <div className="mx-auto max-w-4xl space-y-4 p-2 md:p-4">
                <Heading
                    title="Add driver"
                    description="Create a driver and assign at least one branch"
                />

                <DriverForm
                    action={DriverController.store.form()}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
                    rosterStatuses={rosterStatuses}
                    branches={branches}
                    requireBranches
                    cancelHref={DriverController.index.url()}
                />
            </div>
        </>
    );
}

PlatformDriversCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Drivers', href: '/platform/drivers' },
        { title: 'Create', href: '/platform/drivers/create' },
    ],
};
