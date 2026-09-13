import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import {
    ProductInventoryPanel,
    type BranchInventorySnapshot,
    type BranchPriceSnapshot,
    type BranchProductVariantRow,
    type TransactionsPage,
} from '@/components/branch/product-inventory-panel';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';

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

    return (
        <>
            <Head title={product.name} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={product.name}
                        description={
                            product.category_name ||
                            'Branch inventory and pricing'
                        }
                    />
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

                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="flex flex-col gap-4 px-5 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <CardTitle className="text-base">
                                Branch availability
                            </CardTitle>
                            <CardDescription>
                                Enablement and catalog details for this branch.
                            </CardDescription>
                        </div>
                        {product.inventory?.is_low_stock ? (
                            <Badge variant="destructive">Low stock</Badge>
                        ) : null}
                    </CardHeader>
                    <CardContent className="space-y-5 px-5">
                        <div className="flex items-start gap-3 rounded-xl border border-border/70 bg-muted/20 px-4 py-3">
                            <Checkbox
                                id="branch-enabled"
                                checked={enabled}
                                className="mt-0.5"
                                onCheckedChange={(checked) => {
                                    const next = checked === true;
                                    setEnabled(next);
                                    router.put(
                                        ProductController.toggle.url({
                                            branch: branch.slug,
                                            product: product.slug,
                                        }),
                                        { status: next },
                                        { preserveScroll: true },
                                    );
                                }}
                            />
                            <div className="space-y-0.5">
                                <Label
                                    htmlFor="branch-enabled"
                                    className="font-medium"
                                >
                                    {enabled ? 'Enabled' : 'Disabled'} for this
                                    branch
                                </Label>
                                <p className="text-muted-foreground text-xs">
                                    Disabled products stay in catalog but are
                                    unavailable here.
                                </p>
                            </div>
                        </div>

                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1">
                                <dt className="text-muted-foreground text-sm">
                                    SKU
                                </dt>
                                <dd className="text-sm font-medium">
                                    {product.sku ?? '—'}
                                </dd>
                            </div>
                            <div className="space-y-1 sm:col-span-2">
                                <dt className="text-muted-foreground text-sm">
                                    Description
                                </dt>
                                <dd className="text-sm font-medium whitespace-pre-wrap">
                                    {product.description?.trim() || '—'}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

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
