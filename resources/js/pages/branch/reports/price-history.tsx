import { setLayoutProps } from '@inertiajs/react';
import PriceHistoryController from '@/actions/App/Http/Controllers/Branch/Reports/PriceHistoryController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import type { DataTablePayload } from '@/components/data-table';
import { PriceHistoryPage } from '@/components/stock/price-history-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchPriceHistory({
    table,
    context,
}: {
    table: DataTablePayload<never>;
    context: StockPageContext;
}) {
    const branch = context.branch as Branch;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: `/branch/${branch.slug}/dashboard` },
            {
                title: 'Reports',
                href: PriceHistoryController.index.url(branch.slug),
            },
            {
                title: 'Price history',
                href: PriceHistoryController.index.url(branch.slug),
            },
        ],
    });

    return (
        <PriceHistoryPage
            table={table}
            indexUrl={PriceHistoryController.index.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            hideBranchFilter
        />
    );
}
