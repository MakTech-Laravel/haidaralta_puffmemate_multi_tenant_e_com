import { Head, setLayoutProps } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
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
    address: string | null;
    is_active: boolean;
    avatar_path: string | null;
    avatar_url: string | null;
    vehicle_image_path: string | null;
    vehicle_image_url: string | null;
};

export default function BranchDriversEdit({
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
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Drivers',
                href: DriverController.index.url(branch.slug),
            },
            {
                title: driver.name,
                href: DriverController.show.url({
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

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${driver.name}`}
                    description="Update driver details for this branch"
                />

                <DriverForm
                    action={DriverController.update.form({
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
                        address: driver.address,
                        is_active: driver.is_active,
                        avatar: driver.avatar_path,
                        avatar_url: driver.avatar_url,
                        vehicle_image: driver.vehicle_image_path,
                        vehicle_image_url: driver.vehicle_image_url,
                    }}
                    cancelHref={DriverController.show.url({
                        branch: branch.slug,
                        user: driver.id,
                    })}
                />
            </div>
        </>
    );
}
