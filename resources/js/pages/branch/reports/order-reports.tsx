import { Head } from '@inertiajs/react';

type DriverReportRow = {
    id: number;
    name: string;
    delivered: number;
    failedAttempts: number;
    collected: number;
    collectedCod: number;
    collectedPos: number;
};

type BranchOrderReportsProps = {
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
    drivers: DriverReportRow[];
};

export default function BranchOrderReports({
    branch,
    report,
    drivers,
}: BranchOrderReportsProps) {
    return (
        <>
            <Head title="Order Reports" />
            <div className="space-y-4 p-6">
                <h1 className="text-2xl font-semibold">Order Reports</h1>
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

                <div className="space-y-2">
                    <h2 className="text-lg font-semibold">
                        Driver performance
                    </h2>
                    <div className="border-border overflow-hidden rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="p-2 text-left">Driver</th>
                                    <th className="p-2 text-right">
                                        Delivered
                                    </th>
                                    <th className="p-2 text-right">
                                        Failed attempts
                                    </th>
                                    <th className="p-2 text-right">
                                        Collected (COD)
                                    </th>
                                    <th className="p-2 text-right">
                                        Collected (POS)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {drivers.length === 0 && (
                                    <tr>
                                        <td
                                            className="text-muted-foreground p-2"
                                            colSpan={5}
                                        >
                                            No drivers assigned to this branch
                                            yet.
                                        </td>
                                    </tr>
                                )}
                                {drivers.map((driver) => (
                                    <tr
                                        key={driver.id}
                                        className="border-border border-t"
                                    >
                                        <td className="p-2">{driver.name}</td>
                                        <td className="p-2 text-right">
                                            {driver.delivered}
                                        </td>
                                        <td className="p-2 text-right">
                                            {driver.failedAttempts}
                                        </td>
                                        <td className="p-2 text-right">
                                            ${driver.collectedCod.toFixed(2)}
                                        </td>
                                        <td className="p-2 text-right">
                                            ${driver.collectedPos.toFixed(2)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

BranchOrderReports.layout = (props: BranchOrderReportsProps) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: `/branch/${props.branch.slug}/dashboard` },
        {
            title: 'Reports',
            href: `/branch/${props.branch.slug}/reports/order-reports`,
        },
        {
            title: 'Order Reports',
            href: `/branch/${props.branch.slug}/reports/order-reports`,
        },
    ],
});
