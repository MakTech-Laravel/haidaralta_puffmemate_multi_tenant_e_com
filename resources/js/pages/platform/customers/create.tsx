import { Head } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
import { CustomerForm } from '@/components/admin/customer-form';
import Heading from '@/components/heading';

export default function PlatformCustomersCreate() {
    return (
        <>
            <Head title="Add Customer" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add Customer"
                    description="Create a registered shopper with account, avatar, and addresses."
                />

                <CustomerForm
                    action={CustomerController.store.form()}
                    cancelHref={CustomerController.index.url()}
                />
            </div>
        </>
    );
}

PlatformCustomersCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Customers', href: '/platform/customers' },
        { title: 'Add Customer', href: '/platform/customers/create' },
    ],
};
