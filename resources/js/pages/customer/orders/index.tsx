import { Head, router } from '@inertiajs/react';
import {
    OrderList,
    type CustomerOrder,
} from '@/components/customer/order-list';
import { index as ordersIndex } from '@/routes/customer/orders';

type CustomerOrdersPageProps = {
    orders: CustomerOrder[];
    meta: {
        currentPage: number;
        lastPage: number;
    };
};

export default function CustomerOrdersIndex({
    orders,
    meta,
}: CustomerOrdersPageProps) {
    return (
        <>
            <Head title="Orders" />

            <OrderList
                orders={orders}
                currentPage={meta.currentPage}
                lastPage={meta.lastPage}
                onPageChange={(page) =>
                    router.get(
                        ordersIndex.url(),
                        { page },
                        {
                            preserveState: true,
                            preserveScroll: true,
                            async: true,
                            showProgress: false,
                        },
                    )
                }
            />
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
