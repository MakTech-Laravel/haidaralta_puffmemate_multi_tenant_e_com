import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';
import {
    isBranchLocked,
    isProductLocked,
    stockSelectionQuery,
    type StockPageContext,
} from '@/lib/stock-query';

export function StockPairFields({
    context,
    pageUrl,
    createProductUrl,
    productOptionsUrl,
    branchOptionsUrl,
    allowEmpty = false,
}: {
    context: StockPageContext;
    pageUrl: string;
    createProductUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl?: string;
    allowEmpty?: boolean;
}) {
    const branchLocked = isBranchLocked(context.locked);
    const productLocked = isProductLocked(context.locked);

    const visit = (overrides: {
        branchId?: number | null;
        productId?: number | null;
    }) => {
        router.get(pageUrl, stockSelectionQuery(context, overrides), {
            preserveState: true,
            replace: true,
        });
    };

    const productLabel = context.product
        ? `${context.product.name}${context.product.sku ? ` · ${context.product.sku}` : ''}`
        : null;

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor="stock-branch">Branch</Label>
                {branchLocked && context.branch ? (
                    <LockedValue>{context.branch.name}</LockedValue>
                ) : branchOptionsUrl ? (
                    <InfiniteScrollCombobox
                        id="stock-branch"
                        optionsUrl={branchOptionsUrl}
                        value={
                            context.branch ? String(context.branch.id) : null
                        }
                        onValueChange={(value) =>
                            visit({
                                branchId: value ? Number(value) : null,
                            })
                        }
                        selectedLabel={context.branch?.name}
                        placeholder="Select a branch"
                        searchPlaceholder="Search branches…"
                        emptyText="No branches found."
                        noneLabel={allowEmpty ? 'All branches' : undefined}
                        descriptionKey={null}
                    />
                ) : null}
            </div>
            <div className="grid gap-2">
                <div className="flex items-center justify-between gap-2">
                    <Label htmlFor="stock-product">Product</Label>
                    <Link
                        href={createProductUrl}
                        className="text-primary text-xs font-medium hover:underline"
                    >
                        Add product
                    </Link>
                </div>
                {productLocked && context.product ? (
                    <LockedValue>{productLabel}</LockedValue>
                ) : (
                    <InfiniteScrollCombobox
                        id="stock-product"
                        optionsUrl={productOptionsUrl}
                        value={
                            context.product ? String(context.product.id) : null
                        }
                        onValueChange={(value) =>
                            visit({
                                productId: value ? Number(value) : null,
                            })
                        }
                        selectedLabel={productLabel}
                        placeholder="Select a product"
                        searchPlaceholder="Search products…"
                        emptyText="No products found."
                        noneLabel={allowEmpty ? 'All products' : undefined}
                    />
                )}
            </div>
        </div>
    );
}

function LockedValue({ children }: { children: ReactNode }) {
    return (
        <div className="bg-muted/60 flex h-9 items-center rounded-md border px-3 text-sm font-medium">
            <span className="truncate">{children}</span>
        </div>
    );
}
