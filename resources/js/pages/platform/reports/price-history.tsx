import PriceHistoryController from '@/actions/App/Http/Controllers/Platform/Reports/PriceHistoryController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import type { DataTablePayload } from '@/components/data-table';
import { PriceHistoryPage } from '@/components/stock/price-history-page';

export default function PlatformPriceHistory({
    table,
}: {
    table: DataTablePayload<never>;
}) {
    return (
        <PriceHistoryPage
            table={table}
            indexUrl={PriceHistoryController.index.url()}
            exportUrl={PriceHistoryController.export.url()}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformPriceHistory.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Reports', href: '/platform/reports/price-history' },
        {
            title: 'Price history',
            href: '/platform/reports/price-history',
        },
    ],
};
