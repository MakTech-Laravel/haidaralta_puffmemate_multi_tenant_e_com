import { Head } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string };

type Driver = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    license_number: string | null;
    vehicle_type: string | null;
    availability_status: string | null;
    notes: string | null;
    is_active: boolean;
    branch_ids: number[];
};

export default function PlatformDriversEdit({
    driver,
    branches,
    vehicleTypes,
    availabilityStatuses,
}: {
    driver: Driver;
    branches: BranchOption[];
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
}) {
    return (
        <>
            <Head title={`Edit ${driver.name}`} />

            <div className="mx-auto max-w-3xl space-y-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${driver.name}`}
                    description="Update account, vehicle, and branch assignments"
                />

                <DriverForm
                    action={DriverController.update.form(driver.id)}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
                    branches={branches}
                    requireBranches
                    isEdit
                    defaults={{
                        name: driver.name,
                        phone: driver.phone,
                        email: driver.email,
                        license_number: driver.license_number,
                        vehicle_type: driver.vehicle_type ?? undefined,
                        availability_status:
                            driver.availability_status ?? undefined,
                        notes: driver.notes,
                        is_active: driver.is_active,
                        branch_ids: driver.branch_ids,
                    }}
                    cancelHref={DriverController.show.url(driver.id)}
                />
            </div>
        </>
    );
}

PlatformDriversEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Drivers', href: '/platform/drivers' },
        { title: 'Edit', href: '#' },
    ],
};
