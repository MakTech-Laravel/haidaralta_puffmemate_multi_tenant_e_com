import { Head } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/Platform/CustomerController';
import { CustomerForm } from '@/components/admin/customer-form';
import Heading from '@/components/heading';

type Customer = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    avatar: string | null;
    avatar_url: string | null;
    shipping: {
        address: string | null;
        city: string | null;
        region: string | null;
        zip: string | null;
    } | null;
    billing: {
        name: string | null;
        address: string | null;
        city: string | null;
        region: string | null;
        zip: string | null;
        email: string | null;
        phone: string | null;
    } | null;
};

export default function PlatformCustomersEdit({
    customer,
}: {
    customer: Customer;
}) {
    return (
        <>
            <Head title={`Edit ${customer.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${customer.name}`}
                    description="Update account, avatar, and shipping or billing details."
                />

                <CustomerForm
                    action={CustomerController.update.form(customer.id)}
                    isEdit
                    submitLabel="Save changes"
                    defaults={{
                        name: customer.name,
                        phone: customer.phone,
                        email: customer.email,
                        is_active: customer.is_active,
                        avatar: customer.avatar,
                        avatar_url: customer.avatar_url,
                        shipping: customer.shipping,
                        billing: customer.billing,
                    }}
                    cancelHref={CustomerController.show.url(customer.id)}
                />
            </div>
        </>
    );
}

PlatformCustomersEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Customers', href: '/platform/customers' },
        { title: 'Edit', href: '#' },
    ],
};
