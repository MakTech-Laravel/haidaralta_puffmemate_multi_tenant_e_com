import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import StockTransferController from '@/actions/App/Http/Controllers/Branch/StockTransferController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type StockTransferRow = {
    id: number;
    orderNumber: string | null;
    status: string;
    statusLabel: string;
    sourceName: string | null;
    destinationName: string | null;
    estimatedCostLabel: string;
    items: { name: string; quantity: number }[];
    canApprove: boolean;
    canPick: boolean;
    canDispatch: boolean;
    canReceive: boolean;
};

export default function BranchStockTransfersIndex({
    branch,
    transfers,
}: {
    branch: { id: number; name: string; slug: string };
    transfers: StockTransferRow[];
}) {
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Stock transfers',
                href: `/branch/${branch.slug}/stock-transfers`,
            },
        ],
    });

    return (
        <>
            <Head title="Stock transfers" />
            <div className="space-y-4 p-2 md:p-4">
                <h1 className="text-2xl font-bold tracking-tight">
                    Stock transfers
                </h1>
                {transfers.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No inbound or outbound stock transfers yet.
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {transfers.map((transfer) => (
                            <Card key={transfer.id}>
                                <CardHeader className="flex flex-row items-start justify-between gap-3">
                                    <div>
                                        <CardTitle className="text-base">
                                            {transfer.sourceName} →{' '}
                                            {transfer.destinationName}
                                        </CardTitle>
                                        <p className="text-muted-foreground text-sm">
                                            {transfer.orderNumber ??
                                                'Unlinked order'}{' '}
                                            · {transfer.estimatedCostLabel}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {transfer.statusLabel}
                                    </Badge>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <p className="text-sm">
                                        {transfer.items
                                            .map(
                                                (item) =>
                                                    `${item.name} × ${item.quantity}`,
                                            )
                                            .join(', ')}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {transfer.canApprove ? (
                                            <Button
                                                size="sm"
                                                disabled={processing}
                                                onClick={() => {
                                                    setProcessing(true);
                                                    router.post(
                                                        StockTransferController.approve.url(
                                                            [
                                                                branch.slug,
                                                                transfer.id,
                                                            ],
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                Approve
                                            </Button>
                                        ) : null}
                                        {transfer.canPick ? (
                                            <Button
                                                size="sm"
                                                disabled={processing}
                                                onClick={() => {
                                                    setProcessing(true);
                                                    router.post(
                                                        StockTransferController.pick.url(
                                                            [
                                                                branch.slug,
                                                                transfer.id,
                                                            ],
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                Pick
                                            </Button>
                                        ) : null}
                                        {transfer.canDispatch ? (
                                            <Button
                                                size="sm"
                                                disabled={processing}
                                                onClick={() => {
                                                    setProcessing(true);
                                                    router.post(
                                                        StockTransferController.dispatch.url(
                                                            [
                                                                branch.slug,
                                                                transfer.id,
                                                            ],
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                Dispatch
                                            </Button>
                                        ) : null}
                                        {transfer.canReceive ? (
                                            <Button
                                                size="sm"
                                                disabled={processing}
                                                onClick={() => {
                                                    setProcessing(true);
                                                    router.post(
                                                        StockTransferController.receive.url(
                                                            [
                                                                branch.slug,
                                                                transfer.id,
                                                            ],
                                                        ),
                                                        {},
                                                        {
                                                            onFinish: () =>
                                                                setProcessing(
                                                                    false,
                                                                ),
                                                        },
                                                    );
                                                }}
                                            >
                                                Receive
                                            </Button>
                                        ) : null}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
