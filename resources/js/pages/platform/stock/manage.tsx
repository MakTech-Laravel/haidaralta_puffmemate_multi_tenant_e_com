import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { ManageStockPage } from '@/components/stock/manage-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformManageStock({
    context,
    createProductUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
}) {
    return (
        <ManageStockPage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.manage.url()}
            adjustUrl={StockController.adjust.url()}
        />
    );
}

PlatformManageStock.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Manage stock', href: '/platform/stock/manage' },
    ],
};
