import { Head, router, setLayoutProps } from '@inertiajs/react';
import DriverController from '@/actions/App/Http/Controllers/Platform/DriverController';
import DriverProductAssignController from '@/actions/App/Http/Controllers/Platform/DriverProductAssignController';
import {
    AssignProductsForm,
    type AssignProductOption,
} from '@/components/driver/assign-products-form';
import Heading from '@/components/heading';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';

type Branch = { id: number; name: string; slug: string };

export default function PlatformDriversAssignProducts({
    branch,
    branchOptionsUrl,
    driverOptionsUrl,
    products,
}: {
    branch: Branch | null;
    branchOptionsUrl: string;
    driverOptionsUrl: string | null;
    products: AssignProductOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Drivers', href: DriverController.index.url() },
            {
                title: 'Assign Product',
                href: DriverProductAssignController.create.url(),
            },
        ],
    });

    const selectBranch = (value: string | null): void => {
        router.get(
            DriverProductAssignController.create.url(),
            value === null ? {} : { branch: value },
            {
                preserveState: false,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <>
            <Head title="Assign Product" />

            <div className="flex w-full min-w-0 max-w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign Product"
                    description="Pick a branch, then move that branch’s stock onto its drivers. Each selected driver receives the same quantity."
                />

                <div className="grid max-w-sm gap-2">
                    <Label htmlFor="assign-branch">Branch</Label>
                    <InfiniteScrollCombobox
                        id="assign-branch"
                        optionsUrl={branchOptionsUrl}
                        value={branch !== null ? String(branch.id) : null}
                        selectedLabel={branch?.name ?? null}
                        onValueChange={selectBranch}
                        placeholder="Select a branch"
                        searchPlaceholder="Search branches…"
                        emptyText="No branches found."
                        loadErrorText="Could not load branches."
                        noneLabel="Select a branch"
                        descriptionKey={null}
                    />
                </div>

                <AssignProductsForm
                    driverOptionsUrl={driverOptionsUrl}
                    products={products}
                    storeUrl={DriverProductAssignController.store.url()}
                    cancelUrl={DriverController.index.url()}
                    disabled={branch === null}
                    extraFormData={
                        branch !== null ? { branch_id: branch.id } : {}
                    }
                />
            </div>
        </>
    );
}
