import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Eye } from 'lucide-react';
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
import {
    isBranchLocked,
    isProductLocked,
    type StockPageContext,
} from '@/lib/stock-query';

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
    detailsUrl,
    branchOptionsUrl,
    lockBranch = false,
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
    detailsUrl: string | null;
    branchOptionsUrl?: string;
    lockBranch?: boolean;
    breadcrumbs: { title: string; href: string }[];
}) {
    setLayoutProps({ breadcrumbs });

    const hasPair = Boolean(
        product && inventoryUrl && priceUrl && transactionsUrl,
    );
    const pairLocked =
        isBranchLocked(context.locked) && isProductLocked(context.locked);

    return (
        <>
            <Head title="Manage stock" />

            <div className="flex w-full min-w-0 flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        className="min-w-0"
                        title="Manage stock"
                        description={
                            hasPair
                                ? `Inventory for ${context.branch?.name ?? 'this branch'}`
                                : lockBranch
                                  ? 'Pick a product to manage stock, selling price, and discounts.'
                                  : 'Pick a branch and a product to manage stock, selling price, and discounts.'
                        }
                    />
                    {hasPair ? (
                        <div className="flex max-w-full min-w-0 flex-wrap justify-end gap-2">
                            {allBranchesUrl ? (
                                <Button variant="outline" asChild>
                                    <Link href={allBranchesUrl}>
                                        All branches
                                    </Link>
                                </Button>
                            ) : null}
                            {branchProductsUrl ? (
                                <Button variant="outline" asChild>
                                    <Link
                                        href={branchProductsUrl}
                                        className="max-w-48 min-w-0"
                                        title={
                                            allBranchesUrl && context.branch
                                                ? `${context.branch.name} products`
                                                : undefined
                                        }
                                    >
                                        <span className="truncate">
                                            {allBranchesUrl && context.branch
                                                ? `${context.branch.name} products`
                                                : 'Back'}
                                        </span>
                                    </Link>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <section className="rounded-2xl border border-border/80 bg-card p-5 shadow-sm sm:p-6">
                    <div className="space-y-1">
                        <h2 className="text-base font-semibold tracking-tight">
                            {lockBranch ? 'Product' : 'Branch and product'}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            {hasPair
                                ? pairLocked
                                    ? 'Stock, selling price, and discounts for this listing.'
                                    : lockBranch
                                      ? 'Change the product to load a different listing.'
                                      : 'Change either picker to load a different listing.'
                                : lockBranch
                                  ? 'Select a product. Inventory & pricing appear after it is chosen.'
                                  : 'Select one of each. Inventory & pricing appear after both are chosen.'}
                        </p>
                    </div>
                    <div className="mt-5">
                        <StockPairFields
                            context={context}
                            pageUrl={pageUrl}
                            productOptionsUrl={productOptionsUrl}
                            branchOptionsUrl={branchOptionsUrl}
                            createProductUrl={createProductUrl}
                            lockBranch={lockBranch}
                        />
                    </div>
                </section>

                {hasPair &&
                product &&
                inventoryUrl &&
                priceUrl &&
                transactionsUrl ? (
                    <>
                        {detailsUrl ? (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <p className="text-muted-foreground text-sm">
                                    Catalog listing for {product.name}
                                </p>
                                <Button variant="outline" asChild>
                                    <Link href={detailsUrl}>
                                        <Eye className="size-4" />
                                        View details
                                    </Link>
                                </Button>
                            </div>
                        ) : null}

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
                ) : null}
            </div>
        </>
    );
}
