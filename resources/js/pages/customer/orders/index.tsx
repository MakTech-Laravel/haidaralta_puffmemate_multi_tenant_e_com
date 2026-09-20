import { Head } from '@inertiajs/react';
import { useState } from 'react';
import {
    OrderList,
    type CustomerOrder,
} from '@/components/customer/order-list';

type CustomerOrdersPageProps = {
    orders: CustomerOrder[];
};

export default function CustomerOrdersIndex({
    orders,
}: CustomerOrdersPageProps) {
    const [page, setPage] = useState(1);

    return (
        <>
            <Head title="Orders" />

            <OrderList orders={orders} page={page} onPageChange={setPage} />
        </>
    );
}

CustomerOrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Orders',
            href: '/orders',
        },
    ],
};
