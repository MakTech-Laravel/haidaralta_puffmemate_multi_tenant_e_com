import { Link, router } from '@inertiajs/react';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';
import { stockPageUrl, type StockPageContext } from '@/lib/stock-query';

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
        <div
            className={
                lockBranch ? 'grid min-w-0 gap-4' : 'grid gap-4 sm:grid-cols-2'
            }
        >
            {lockBranch ? null : (
                <div className="grid min-w-0 gap-2">
                    <div className="flex h-8 items-center">
                        <Label htmlFor="stock-manage-branch">Branch</Label>
                    </div>
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
                </div>
            )}
            <div className="grid min-w-0 gap-2">
                <div className="flex h-8 items-center justify-between gap-2">
                    <Label htmlFor="stock-manage-product">Product</Label>
                    <Link
                        href={createProductUrl}
                        className="text-primary shrink-0 text-xs font-medium hover:underline"
                    >
                        Add product
                    </Link>
                </div>
                <InfiniteScrollCombobox
                    id="stock-manage-product"
                    optionsUrl={productOptionsUrl}
                    value={
                        context.product ? String(context.product.id) : null
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
            </div>
        </div>
    );
}
