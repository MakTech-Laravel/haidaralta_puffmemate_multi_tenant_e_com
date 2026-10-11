import { Head, setLayoutProps } from '@inertiajs/react';
import StockTransferController from '@/actions/App/Http/Controllers/Branch/StockTransferController';
import type { DataTablePayload } from '@/components/data-table';
import {
    StockTransfersPage,
    type StockTransferRow,
} from '@/components/stock/stock-transfers-page';

type Branch = { id: number; name: string; slug: string };

export default function BranchStockTransfersIndex({
    branch,
    table,
}: {
    branch: Branch;
    table: DataTablePayload<StockTransferRow>;
}) {
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
            <StockTransfersPage
                table={table}
                indexUrl={StockTransferController.index.url(branch.slug)}
                description={`Transfers in or out of ${branch.name}.`}
                actionUrl={(id, action) => {
                    const urls = {
                        approve: StockTransferController.approve.url([
                            branch.slug,
                            id,
                        ]),
                        pick: StockTransferController.pick.url([
                            branch.slug,
                            id,
                        ]),
                        dispatch: StockTransferController.dispatch.url([
                            branch.slug,
                            id,
                        ]),
                        receive: StockTransferController.receive.url([
                            branch.slug,
                            id,
                        ]),
                    };

                    return urls[action];
                }}
            />
        </>
    );
}
