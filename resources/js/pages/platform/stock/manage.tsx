import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import {
    ManageStockPage,
    type StockManageProduct,
} from '@/components/stock/manage-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformManageStock({
    context,
    product,
    transactionTypes,
    adjustmentReasons,
    discountTypes,
    createProductUrl,
    inventoryUrl,
    priceUrl,
    transactionsUrl,
    priceHistoryUrl,
    allBranchesUrl,
    branchProductsUrl,
}: {
    context: StockPageContext;
    product: StockManageProduct | null;
    transactionTypes: { value: string; label: string }[];
    adjustmentReasons: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
    createProductUrl: string;
    inventoryUrl: string | null;
    priceUrl: string | null;
    transactionsUrl: string | null;
    priceHistoryUrl: string | null;
    allBranchesUrl: string | null;
    branchProductsUrl: string | null;
}) {
    return (
        <ManageStockPage
            context={context}
            product={product}
            transactionTypes={transactionTypes}
            adjustmentReasons={adjustmentReasons}
            discountTypes={discountTypes}
            createProductUrl={createProductUrl}
            pageUrl={StockController.manage.url()}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
            inventoryUrl={inventoryUrl}
            priceUrl={priceUrl}
            transactionsUrl={transactionsUrl}
            priceHistoryUrl={priceHistoryUrl}
            allBranchesUrl={allBranchesUrl}
            branchProductsUrl={branchProductsUrl}
            detailsUrl={
                product ? ProductController.show.url(product.slug) : null
            }
            breadcrumbs={[
                { title: 'Dashboard', href: '/platform/dashboard' },
                { title: 'Products', href: '/platform/products' },
                {
                    title: 'Manage stock',
                    href: StockController.manage.url(),
                },
            ]}
        />
    );
}
