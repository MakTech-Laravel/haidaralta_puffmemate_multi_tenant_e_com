import { Head, setLayoutProps } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Branch/DriverController';
import DriverProductAssignController from '@/actions/App/Http/Controllers/Branch/DriverProductAssignController';
import {
    AssignProductsForm,
    type AssignProductOption,
} from '@/components/driver/assign-products-form';
import Heading from '@/components/heading';

type Branch = { id: number; name: string; slug: string };

export default function BranchDriversAssignProducts({
    branch,
    driverOptionsUrl,
    products,
}: {
    branch: Branch;
    driverOptionsUrl: string;
    products: AssignProductOption[];
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
                title: 'Assign Products',
                href: DriverProductAssignController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Assign Products" />

            <div className="flex w-full min-w-0 max-w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign Products"
                    description={`Move branch stock onto ${branch.name} drivers. Each selected driver receives the same quantity.`}
                />

                <AssignProductsForm
                    driverOptionsUrl={driverOptionsUrl}
                    products={products}
                    storeUrl={DriverProductAssignController.store.url(
                        branch.slug,
                    )}
                    cancelUrl={DriverController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
