import { Head, Link, setLayoutProps } from '@inertiajs/react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { BranchProductSummary } from '@/components/branch/branch-product-summary';
import {
    ProductInventoryPanel,
    type BranchInventorySnapshot,
    type BranchPriceSnapshot,
    type BranchProductVariantRow,
} from '@/components/branch/product-inventory-panel';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { type ColorPickerValue } from '@/lib/color-picker';
import { stockPageUrl } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

type ProductShow = {
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

export default function PlatformBranchProductShow({
    branch,
    product,
    transactionTypes,
    adjustmentReasons,
    discountTypes,
}: {
    branch: Branch;
    product: ProductShow;
    transactionTypes: { value: string; label: string }[];
    adjustmentReasons: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Products',
                href: BranchInventoryController.index.url(branch.slug),
            },
            {
                title: product.name,
                href: BranchInventoryController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={`${product.name} · ${branch.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="[&_header]:mb-0">
                        <Heading
                            title={product.name}
                            description={`Inventory for ${branch.name}`}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={BranchInventoryController.forProduct.url(
                                    product.slug,
                                )}
                            >
                                All branches
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={BranchInventoryController.index.url(
                                    branch.slug,
                                )}
                            >
                                {branch.name} products
                            </Link>
                        </Button>
                    </div>
                </div>

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
                    categoryHref={(slug) => CategoryController.show.url(slug)}
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
                    inventoryUrl={BranchInventoryController.adjustInventory.url(
                        {
                            branch: branch.slug,
                            product: product.slug,
                        },
                    )}
                    priceUrl={BranchInventoryController.upsertPrice.url({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                    transactionsUrl={stockPageUrl(
                        StockController.transactions.url(),
                        {
                            productId: product.id,
                            branchId: branch.id,
                        },
                    )}
                />
            </div>
        </>
    );
}
