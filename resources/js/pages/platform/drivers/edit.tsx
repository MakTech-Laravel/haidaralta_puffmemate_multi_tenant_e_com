import { Head } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type BranchOption = { id: number; name: string; slug: string; code?: string | null };

type Driver = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    license_number: string | null;
    vehicle_type: string | null;
    availability_status: string | null;
    notes: string | null;
    address: string | null;
    is_active: boolean;
    branch_ids: number[];
    avatar_path: string | null;
    avatar_url: string | null;
    vehicle_image_path: string | null;
    vehicle_image_url: string | null;
};

export default function PlatformDriversEdit({
    driver,
    branches,
    vehicleTypes,
    availabilityStatuses,
    rosterStatuses,
}: {
    driver: Driver;
    branches: BranchOption[];
    vehicleTypes: Option[];
    availabilityStatuses: Option[];
    rosterStatuses: Option[];
}) {
    return (
        <>
            <Head title={`Edit ${driver.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${driver.name}`}
                    description="Update account, vehicle, and branch assignments"
                />

                <DriverForm
                    action={DriverController.update.form(driver.id)}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
                    rosterStatuses={rosterStatuses}
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
                        address: driver.address,
                        is_active: driver.is_active,
                        branch_ids: driver.branch_ids,
                        avatar: driver.avatar_path,
                        avatar_url: driver.avatar_url,
                        vehicle_image: driver.vehicle_image_path,
                        vehicle_image_url: driver.vehicle_image_url,
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
