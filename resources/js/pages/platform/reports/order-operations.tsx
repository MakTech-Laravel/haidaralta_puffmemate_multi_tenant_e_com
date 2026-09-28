import { Head } from '@inertiajs/react';

type PlatformOrderOperationsProps = {
    report: {
        orders: number;
        delivered: number;
        sales: number;
        transferCost: number;
        payments: Record<string, number>;
    };
};

export default function PlatformOrderOperations({
    report,
}: PlatformOrderOperationsProps) {
    return (
        <>
            <Head title="Order operations" />
            <div className="space-y-4 p-6">
                <h1 className="text-2xl font-semibold">Order operations</h1>
                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">Orders</dt>
                        <dd className="text-xl font-semibold">{report.orders}</dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">Delivered</dt>
                        <dd className="text-xl font-semibold">{report.delivered}</dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">Sales</dt>
                        <dd className="text-xl font-semibold">
                            ${report.sales.toFixed(2)}
                        </dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">
                            Transfer cost
                        </dt>
                        <dd className="text-xl font-semibold">
                            ${report.transferCost.toFixed(2)}
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

PlatformOrderOperations.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Reports', href: '/platform/reports/order-operations' },
        {
            title: 'Order operations',
            href: '/platform/reports/order-operations',
        },
    ],
};
