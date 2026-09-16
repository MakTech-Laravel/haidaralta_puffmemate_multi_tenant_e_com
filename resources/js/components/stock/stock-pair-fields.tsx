import { Link, router } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    allowEmpty = false,
}: {
    context: StockPageContext;
    pageUrl: string;
    createProductUrl: string;
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

    return (
        <div className="grid gap-4 md:grid-cols-2">
            <div className="grid gap-2">
                <Label>Branch</Label>
                {branchLocked && context.branch ? (
                    <p className="bg-muted rounded-md border px-3 py-2 text-sm font-medium">
                        {context.branch.name}
                    </p>
                ) : (
                    <Select
                        value={
                            context.branch
                                ? String(context.branch.id)
                                : allowEmpty
                                  ? 'all'
                                  : ''
                        }
                        onValueChange={(value) =>
                            visit({
                                branchId:
                                    value === 'all' ? null : Number(value),
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select a branch" />
                        </SelectTrigger>
                        <SelectContent>
                            {allowEmpty ? (
                                <SelectItem value="all">All branches</SelectItem>
                            ) : null}
                            {context.branch_options.map((branch) => (
                                <SelectItem
                                    key={branch.id}
                                    value={String(branch.id)}
                                >
                                    {branch.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </div>
            <div className="grid gap-2">
                <div className="flex items-center justify-between gap-2">
                    <Label>Product</Label>
                    <ButtonLink href={createProductUrl} />
                </div>
                {productLocked && context.product ? (
                    <p className="bg-muted rounded-md border px-3 py-2 text-sm font-medium">
                        {context.product.name}
                        {context.product.sku
                            ? ` · ${context.product.sku}`
                            : ''}
                    </p>
                ) : (
                    <Select
                        value={
                            context.product
                                ? String(context.product.id)
                                : allowEmpty
                                  ? 'all'
                                  : ''
                        }
                        onValueChange={(value) =>
                            visit({
                                productId:
                                    value === 'all' ? null : Number(value),
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select a product" />
                        </SelectTrigger>
                        <SelectContent>
                            {allowEmpty ? (
                                <SelectItem value="all">All products</SelectItem>
                            ) : null}
                            {context.product_options.map((product) => (
                                <SelectItem
                                    key={product.id}
                                    value={String(product.id)}
                                >
                                    {product.name}
                                    {product.sku ? ` · ${product.sku}` : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            </div>
        </div>
    );
}

function ButtonLink({ href }: { href: string }) {
    return (
        <Link
            href={href}
            className="text-primary text-xs font-medium hover:underline"
        >
            Add product
        </Link>
    );
}
