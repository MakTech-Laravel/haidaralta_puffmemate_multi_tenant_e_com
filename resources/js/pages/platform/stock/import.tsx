import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { ImportStockPage } from '@/components/stock/import-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformImportStock({
    context,
    createProductUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
}) {
    return (
        <ImportStockPage
            context={context}
            createProductUrl={createProductUrl}
            storeUrl={StockController.storeImport.url()}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformImportStock.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Import stock', href: '/platform/stock/import' },
    ],
};
