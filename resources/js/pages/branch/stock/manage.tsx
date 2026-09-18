import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import {
    ManageStockPage,
    type StockManageProduct,
} from '@/components/stock/manage-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchManageStock({
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
    const branch = context.branch as Branch;

    return (
        <ManageStockPage
            context={context}
            product={product}
            transactionTypes={transactionTypes}
            adjustmentReasons={adjustmentReasons}
            discountTypes={discountTypes}
            createProductUrl={createProductUrl}
            pageUrl={StockController.manage.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            inventoryUrl={inventoryUrl}
            priceUrl={priceUrl}
            transactionsUrl={transactionsUrl}
            priceHistoryUrl={priceHistoryUrl}
            allBranchesUrl={allBranchesUrl}
            branchProductsUrl={branchProductsUrl}
            detailsUrl={
                product
                    ? ProductController.show.url({
                          branch: branch.slug,
                          product: product.slug,
                      })
                    : null
            }
            lockBranch
            breadcrumbs={[
                {
                    title: 'Dashboard',
                    href: `/branch/${branch.slug}/dashboard`,
                },
                {
                    title: 'Products',
                    href: ProductController.index.url(branch.slug),
                },
                {
                    title: 'Manage stock',
                    href: StockController.manage.url(branch.slug),
                },
            ]}
        />
    );
}
