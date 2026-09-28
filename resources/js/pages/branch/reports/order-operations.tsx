import { Head } from '@inertiajs/react';

type BranchOrderOperationsProps = {
    branch: { slug: string; name: string };
    report: {
        fulfillments: number;
        delivered: number;
        sales: number;
        outboundTransfers: number;
        inboundTransfers: number;
        outboundTransferCost: number;
        unverifiedCollections: number;
    };
};

export default function BranchOrderOperations({
    branch,
    report,
}: BranchOrderOperationsProps) {
    return (
        <>
            <Head title="Order operations" />
            <div className="space-y-4 p-6">
                <h1 className="text-2xl font-semibold">Order operations</h1>
                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">Fulfillments</dt>
                        <dd className="text-xl font-semibold">{report.fulfillments}</dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">Sales</dt>
                        <dd className="text-xl font-semibold">
                            ${report.sales.toFixed(2)}
                        </dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">
                            Outbound transfers
                        </dt>
                        <dd className="text-xl font-semibold">
                            {report.outboundTransfers}
                        </dd>
                    </div>
                    <div className="border-border rounded-lg border p-4">
                        <dt className="text-muted-foreground text-sm">
                            Unverified collections
                        </dt>
                        <dd className="text-xl font-semibold">
                            {report.unverifiedCollections}
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}

BranchOrderOperations.layout = (props: BranchOrderOperationsProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: `/branch/${props.branch.slug}/dashboard` },
        {
            title: 'Reports',
            href: `/branch/${props.branch.slug}/reports/order-operations`,
        },
        {
            title: 'Order operations',
            href: `/branch/${props.branch.slug}/reports/order-operations`,
        },
    ],
});
