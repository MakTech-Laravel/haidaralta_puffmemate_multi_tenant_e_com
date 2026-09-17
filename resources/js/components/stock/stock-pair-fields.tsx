import { Link, router } from '@inertiajs/react';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';
import {
    isBranchLocked,
    isProductLocked,
    stockPageUrl,
    type StockPageContext,
} from '@/lib/stock-query';

function LockedValue({ children }: { children: string }) {
    return (
        <div className="bg-muted/60 flex h-9 min-w-0 items-center rounded-md border px-3 text-sm font-medium">
            <span className="truncate">{children}</span>
        </div>
    );
}

export function StockPairFields({
    context,
    pageUrl,
    productOptionsUrl,
    branchOptionsUrl,
    createProductUrl,
    lockBranch = false,
}: {
    context: StockPageContext;
    pageUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl?: string;
    createProductUrl: string;
    lockBranch?: boolean;
}) {
    const branchLocked = lockBranch || isBranchLocked(context.locked);
    const productLocked = isProductLocked(context.locked);

    const visit = (next: {
        branchId?: number | null;
        productId?: number | null;
    }) => {
        const target = stockPageUrl(pageUrl, {
            productId:
                next.productId === undefined
                    ? context.product?.id
                    : next.productId,
            branchId:
                next.branchId === undefined
                    ? context.branch?.id
                    : next.branchId,
            locked: context.locked,
        });
        const current = `${window.location.pathname}${window.location.search}`;

        if (target === current) {
            return;
        }

        router.get(
            target,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid min-w-0 gap-2">
                <div className="flex h-8 items-center">
                    <Label htmlFor="stock-manage-branch">Branch</Label>
                </div>
                {branchLocked && context.branch ? (
                    <LockedValue>{context.branch.name}</LockedValue>
                ) : (
                    <InfiniteScrollCombobox
                        id="stock-manage-branch"
                        optionsUrl={branchOptionsUrl ?? ''}
                        value={
                            context.branch ? String(context.branch.id) : null
                        }
                        selectedLabel={context.branch?.name}
                        onValueChange={(value) =>
                            visit({
                                branchId: value ? Number(value) : null,
                            })
                        }
                        placeholder="Select a branch"
                        searchPlaceholder="Search branches"
                        emptyText="No branches found."
                    />
                )}
            </div>
            <div className="grid min-w-0 gap-2">
                <div className="flex h-8 items-center justify-between gap-2">
                    <Label htmlFor="stock-manage-product">Product</Label>
                    {productLocked ? null : (
                        <Link
                            href={createProductUrl}
                            className="text-primary shrink-0 text-xs font-medium hover:underline"
                        >
                            Add product
                        </Link>
                    )}
                </div>
                {productLocked && context.product ? (
                    <LockedValue>
                        {context.product.sku
                            ? `${context.product.name} · ${context.product.sku}`
                            : context.product.name}
                    </LockedValue>
                ) : (
                    <InfiniteScrollCombobox
                        id="stock-manage-product"
                        optionsUrl={productOptionsUrl}
                        value={
                            context.product
                                ? String(context.product.id)
                                : null
                        }
                        selectedLabel={context.product?.name}
                        onValueChange={(value) =>
                            visit({
                                productId: value ? Number(value) : null,
                            })
                        }
                        placeholder="Select a product"
                        searchPlaceholder="Search products"
                        emptyText="No products found."
                    />
                )}
            </div>
        </div>
    );
}
