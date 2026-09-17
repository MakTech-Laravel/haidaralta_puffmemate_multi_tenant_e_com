import { Link, router } from '@inertiajs/react';
import { Building2, Package, Plus } from 'lucide-react';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    isBranchLocked,
    isProductLocked,
    stockPageUrl,
    type StockPageContext,
} from '@/lib/stock-query';

function LockedValue({ children }: { children: string }) {
    return (
        <div className="bg-muted/60 flex h-9 items-center rounded-md border px-3 text-sm font-medium">
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
            <div className="grid gap-2">
                <Label
                    htmlFor="stock-manage-branch"
                    className="flex items-center gap-2"
                >
                    <Building2 className="text-muted-foreground size-3.5" />
                    Branch
                </Label>
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
            <div className="grid gap-2">
                <div className="flex items-center justify-between gap-2">
                    <Label
                        htmlFor="stock-manage-product"
                        className="flex items-center gap-2"
                    >
                        <Package className="text-muted-foreground size-3.5" />
                        Product
                    </Label>
                    {productLocked ? null : (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={createProductUrl}>
                                <Plus className="size-3.5" />
                                Add product
                            </Link>
                        </Button>
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
