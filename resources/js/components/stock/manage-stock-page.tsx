import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { BranchProductSummary } from '@/components/branch/branch-product-summary';
import {
    ProductInventoryPanel,
    type BranchInventorySnapshot,
    type BranchPriceSnapshot,
    type BranchProductVariantRow,
} from '@/components/branch/product-inventory-panel';
import Heading from '@/components/heading';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import { type ColorPickerValue } from '@/lib/color-picker';
import type { StockPageContext } from '@/lib/stock-query';

export type StockManageProduct = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    brand: string | null;
    description?: string | null;
    tags: string[];
    specifications: { label: string; value: string }[];
    status: boolean;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    categories: { id: number; name: string; slug: string }[];
    category_name: string;
    gallery?: {
        id: number;
        url: string;
        path?: string;
        sort_order?: number;
    }[];
    created_at: string | null;
    updated_at: string | null;
    created_by_name: string | null;
    variants: BranchProductVariantRow[];
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variant_inventories: Record<string, BranchInventorySnapshot>;
    variant_prices: Record<string, BranchPriceSnapshot>;
};

export function ManageStockPage({
    context,
    product,
    transactionTypes,
    adjustmentReasons,
    discountTypes,
    createProductUrl,
    pageUrl,
    productOptionsUrl,
    inventoryUrl,
    priceUrl,
    transactionsUrl,
    allBranchesUrl,
    branchProductsUrl,
    editUrl,
    branchOptionsUrl,
    lockBranch = false,
    categoryHref,
    breadcrumbs,
}: {
    context: StockPageContext;
    product: StockManageProduct | null;
    transactionTypes: { value: string; label: string }[];
    adjustmentReasons: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
    createProductUrl: string;
    pageUrl: string;
    productOptionsUrl: string;
    inventoryUrl: string | null;
    priceUrl: string | null;
    transactionsUrl: string | null;
    allBranchesUrl: string | null;
    branchProductsUrl: string | null;
    editUrl: string | null;
    branchOptionsUrl?: string;
    lockBranch?: boolean;
    categoryHref?: (slug: string) => string;
    breadcrumbs: { title: string; href: string }[];
}) {
    setLayoutProps({ breadcrumbs });

    const title = product?.name ?? 'Manage stock';
    const description = product
        ? `Inventory for ${context.branch?.name ?? 'this branch'}`
        : 'Pick a branch and a product to manage stock, selling price, and discounts.';

    return (
        <>
            <Head title={title} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="[&_header]:mb-0">
                        <Heading title={title} description={description} />
                    </div>
                    {product ? (
                        <div className="flex flex-wrap gap-2">
                            {allBranchesUrl ? (
                                <Button variant="outline" asChild>
                                    <Link href={allBranchesUrl}>
                                        All branches
                                    </Link>
                                </Button>
                            ) : null}
                                    {branchProductsUrl ? (
                                <Button variant="outline" asChild>
                                    <Link href={branchProductsUrl}>
                                        {allBranchesUrl && context.branch
                                            ? `${context.branch.name} products`
                                            : 'Back'}
                                    </Link>
                                </Button>
                            ) : null}
                            {editUrl ? (
                                <Button asChild>
                                    <Link href={editUrl}>Edit</Link>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <StockPairFields
                    context={context}
                    pageUrl={pageUrl}
                    productOptionsUrl={productOptionsUrl}
                    branchOptionsUrl={branchOptionsUrl}
                    createProductUrl={createProductUrl}
                    lockBranch={lockBranch}
                />

                {product &&
                inventoryUrl &&
                priceUrl &&
                transactionsUrl ? (
                    <>
                        <BranchProductSummary
                            name={product.name}
                            sku={product.sku}
                            slug={product.slug}
                            brand={product.brand}
                            description={product.description}
                            status={product.status}
                            imageUrl={product.image_url}
                            colorCode={product.color_code}
                            gallery={product.gallery ?? []}
                            categories={product.categories}
                            categoryName={product.category_name}
                            tags={product.tags}
                            specifications={product.specifications}
                            createdAt={product.created_at}
                            updatedAt={product.updated_at}
                            createdByName={product.created_by_name}
                            categoryHref={categoryHref}
                        />

                        <ProductInventoryPanel
                            variants={product.variants}
                            inventory={product.inventory}
                            price={product.price}
                            variantInventories={product.variant_inventories}
                            variantPrices={product.variant_prices}
                            transactionTypes={transactionTypes}
                            adjustmentReasons={adjustmentReasons}
                            discountTypes={discountTypes}
                            inventoryUrl={inventoryUrl}
                            priceUrl={priceUrl}
                            transactionsUrl={transactionsUrl}
                        />
                    </>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Choose a branch and a product to manage stock,
                        selling price, and discounts.
                    </p>
                )}
            </div>
        </>
    );
}
