import { Head, router } from '@inertiajs/react';
import { WholesaleRequestList } from '@/components/customer/wholesale-request-list';
import type { CustomerWholesaleRequest } from '@/components/customer/wholesale-request-types';
import { index as wholesaleRequestsIndex } from '@/routes/customer/wholesale-requests';

type CustomerWholesaleRequestsPageProps = {
    requests: CustomerWholesaleRequest[];
    meta: {
        currentPage: number;
        lastPage: number;
    };
};

export default function CustomerWholesaleRequestsIndex({
    requests,
    meta,
}: CustomerWholesaleRequestsPageProps) {
    return (
        <>
            <Head title="Wholesale requests" />

            <WholesaleRequestList
                requests={requests}
                currentPage={meta.currentPage}
                lastPage={meta.lastPage}
                onPageChange={(page) =>
                    router.get(
                        wholesaleRequestsIndex.url(),
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

CustomerWholesaleRequestsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Wholesale requests',
            href: wholesaleRequestsIndex.url(),
        },
    ],
};
