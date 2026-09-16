import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { UpdatePricePage } from '@/components/stock/update-price-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformUpdateStockPrice({
    context,
    createProductUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
}) {
    return (
        <UpdatePricePage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.price.url()}
            updateUrl={StockController.updatePrice.url()}
        />
    );
}

PlatformUpdateStockPrice.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Update price', href: '/platform/stock/price' },
    ],
};
