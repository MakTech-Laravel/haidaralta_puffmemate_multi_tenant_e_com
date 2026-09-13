import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import { BranchProductSummary } from '@/components/branch/branch-product-summary';
import {
    ProductInventoryPanel,
    type BranchInventorySnapshot,
    type BranchPriceSnapshot,
    type BranchProductVariantRow,
    type TransactionsPage,
} from '@/components/branch/product-inventory-panel';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Branch = { id: number; name: string; slug: string };

type ProductShow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    description: string | null;
    status: boolean;
    image_url: string | null;
    category_name: string;
    variants: BranchProductVariantRow[];
    enabled: boolean;
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variant_inventories: Record<string, BranchInventorySnapshot>;
    variant_prices: Record<string, BranchPriceSnapshot>;
};

export default function BranchProductShow({
    branch,
    product,
    transactions,
    transactionTypes,
    discountTypes,
}: {
    branch: Branch;
    product: ProductShow;
    transactions: TransactionsPage;
    transactionTypes: { value: string; label: string }[];
    discountTypes: { value: string; label: string }[];
}) {
    const [enabled, setEnabled] = useState(product.enabled);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Products',
                href: ProductController.index.url(branch.slug),
            },
            {
                title: product.name,
                href: ProductController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    const toggleEnabled = (next: boolean) => {
        setEnabled(next);
        router.put(
            ProductController.toggle.url({
                branch: branch.slug,
                product: product.slug,
            }),
            { status: next },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={product.name} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar className="size-14 rounded-xl border border-border/70">
                            {product.image_url ? (
                                <AvatarImage
                                    src={product.image_url}
                                    alt={product.name}
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl text-base">
                                {product.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col gap-2">
                            <Heading
                                title={product.name}
                                description={
                                    product.sku
                                        ? `SKU ${product.sku}`
                                        : 'Branch inventory and pricing'
                                }
                            />
                            <div className="flex flex-wrap gap-2">
                                <Badge
                                    variant={
                                        enabled ? 'default' : 'secondary'
                                    }
                                >
                                    {enabled ? 'Enabled' : 'Disabled'}
                                </Badge>
                                {product.inventory?.is_low_stock ? (
                                    <Badge variant="destructive">
                                        Low stock
                                    </Badge>
                                ) : null}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={ProductController.index.url(branch.slug)}
                            >
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={ProductController.edit.url({
                                    branch: branch.slug,
                                    product: product.slug,
                                })}
                            >
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <BranchProductSummary
                    name={product.name}
                    sku={product.sku}
                    description={product.description}
                    categoryName={product.category_name}
                    imageUrl={product.image_url}
                    enabled={enabled}
                    lowStock={Boolean(product.inventory?.is_low_stock)}
                    onEnabledChange={toggleEnabled}
                    enabledLabel="Enabled for this branch"
                />

                <ProductInventoryPanel
                    variants={product.variants}
                    inventory={product.inventory}
                    price={product.price}
                    variantInventories={product.variant_inventories}
                    variantPrices={product.variant_prices}
                    transactions={transactions}
                    transactionTypes={transactionTypes}
                    discountTypes={discountTypes}
                    inventoryUrl={ProductController.adjustInventory.url({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                    priceUrl={ProductController.upsertPrice.url({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                    showUrl={ProductController.show.url({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                />
            </div>
        </>
    );
}
