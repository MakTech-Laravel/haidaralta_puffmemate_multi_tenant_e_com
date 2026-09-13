import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import {
    ProductInventoryPanel,
    type BranchInventorySnapshot,
    type BranchInventoryTransaction,
    type BranchPriceSnapshot,
    type BranchProductVariantRow,
} from '@/components/branch/product-inventory-panel';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';

type Branch = { id: number; name: string; slug: string };

type ProductShow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    status: boolean;
    image_url: string | null;
    category_name: string;
    variants: BranchProductVariantRow[];
    enabled: boolean;
    inventory: BranchInventorySnapshot | null;
    price: BranchPriceSnapshot | null;
    variant_inventories: Record<string, BranchInventorySnapshot>;
    variant_prices: Record<string, BranchPriceSnapshot>;
    transactions: BranchInventoryTransaction[];
};

export default function PlatformBranchProductShow({
    branch,
    product,
    transactionTypes,
}: {
    branch: Branch;
    product: ProductShow;
    transactionTypes: { value: string; label: string }[];
}) {
    const [enabled, setEnabled] = useState(product.enabled);

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

            <div className="mx-auto flex max-w-4xl flex-col gap-8 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={product.name}
                        description={`Inventory for ${branch.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link
                            href={BranchInventoryController.index.url(
                                branch.slug,
                            )}
                        >
                            Back
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            checked={enabled}
                            onCheckedChange={(checked) => {
                                const next = checked === true;
                                setEnabled(next);
                                router.put(
                                    BranchInventoryController.toggle.url({
                                        branch: branch.slug,
                                        product: product.slug,
                                    }),
                                    { status: next },
                                    { preserveScroll: true },
                                );
                            }}
                        />
                        <span className="text-sm">
                            {enabled ? 'Enabled' : 'Disabled'} at this branch
                        </span>
                    </div>
                    {product.inventory?.is_low_stock ? (
                        <Badge variant="destructive">Low stock</Badge>
                    ) : null}
                </div>

                <div className="grid gap-2 text-sm">
                    <div>
                        <span className="text-muted-foreground">SKU: </span>
                        {product.sku ?? '—'}
                    </div>
                    <div>
                        <span className="text-muted-foreground">
                            Categories:{' '}
                        </span>
                        {product.category_name || '—'}
                    </div>
                </div>

                <ProductInventoryPanel
                    variants={product.variants}
                    inventory={product.inventory}
                    price={product.price}
                    variantInventories={product.variant_inventories}
                    variantPrices={product.variant_prices}
                    transactions={product.transactions}
                    transactionTypes={transactionTypes}
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
                />
            </div>
        </>
    );
}
