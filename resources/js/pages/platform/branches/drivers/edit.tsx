import { Head, setLayoutProps } from '@inertiajs/react';
import BranchDriverController from '@/actions/App/Http/Controllers/Platform/BranchDriverController';
import { DriverForm } from '@/components/admin/driver-form';
import Heading from '@/components/heading';

type Option = { value: string; label: string };
type Branch = { id: number; name: string; slug: string };

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
};

export default function PlatformBranchDriversEdit({
    branch,
    driver,
    vehicleTypes,
    availabilityStatuses,
}: {
    branch: Branch;
    driver: Driver;
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
                title: driver.name,
                href: BranchDriverController.show.url({
                    branch: branch.slug,
                    user: driver.id,
                }),
            },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${driver.name}`} />

            <div className="mx-auto max-w-3xl space-y-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${driver.name}`}
                    description="Update driver details for this branch"
                />

                <DriverForm
                    action={BranchDriverController.update.form({
                        branch: branch.slug,
                        user: driver.id,
                    })}
                    vehicleTypes={vehicleTypes}
                    availabilityStatuses={availabilityStatuses}
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
                    }}
                    cancelHref={BranchDriverController.show.url({
                        branch: branch.slug,
                        user: driver.id,
                    })}
                />
            </div>
        </>
    );
}
