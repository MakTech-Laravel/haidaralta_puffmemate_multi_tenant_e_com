import { Head, setLayoutProps } from '@inertiajs/react';
import StockTransferController from '@/actions/App/Http/Controllers/Platform/StockTransferController';
import {
    StockTransfersPage,
    type StockTransferRow,
} from '@/components/stock/stock-transfers-page';
import type { DataTablePayload } from '@/components/data-table';

export default function PlatformStockTransfersIndex({
    table,
}: {
    table: DataTablePayload<StockTransferRow>;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Stock transfers', href: '/platform/stock-transfers' },
        ],
    });

    return (
        <>
            <Head title="Stock transfers" />
            <StockTransfersPage
                table={table}
                indexUrl={StockTransferController.index.url()}
                description="Move stock between branches for an order."
                actionUrl={(id, action) => {
                    const urls = {
                        approve: StockTransferController.approve.url(id),
                        pick: StockTransferController.pick.url(id),
                        dispatch: StockTransferController.dispatch.url(id),
                        receive: StockTransferController.receive.url(id),
                    };

                    return urls[action];
                }}
            />
        </>
    );
}
