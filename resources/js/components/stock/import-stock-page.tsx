import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { isBranchLocked, isProductLocked, type StockPageContext } from '@/lib/stock-query';

export function ImportStockPage({
    context,
    createProductUrl,
    storeUrl,
    lockBranch = false,
}: {
    context: StockPageContext;
    createProductUrl: string;
    storeUrl: string;
    lockBranch?: boolean;
}) {
    const branchLocked = lockBranch || isBranchLocked(context.locked);
    const productLocked = isProductLocked(context.locked);
    const form = useForm({
        branch_ids: branchLocked && context.branch ? [context.branch.id] : ([] as number[]),
        product_ids:
            productLocked && context.product ? [context.product.id] : ([] as number[]),
        quantity: 1,
    });

    const toggle = (
        key: 'branch_ids' | 'product_ids',
        id: number,
        checked: boolean,
    ) => {
        const current = form.data[key];
        form.setData(
            key,
            checked
                ? [...current, id]
                : current.filter((value) => value !== id),
        );
    };

    return (
        <>
            <Head title="Import stock" />
            <div className="mx-auto max-w-3xl space-y-6 p-2 md:p-4">
                <Heading
                    title="Import stock"
                    description="Add the same quantity to many branch and product pairs"
                />
                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(storeUrl, { preserveScroll: true });
                    }}
                >
                    <div className="grid gap-6 md:grid-cols-2">
                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium">
                                Branches
                            </legend>
                            {branchLocked && context.branch ? (
                                <p className="bg-muted rounded-md border px-3 py-2 text-sm font-medium">
                                    {context.branch.name}
                                </p>
                            ) : (
                                <div className="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
                                    {context.branch_options.map((branch) => (
                                        <label
                                            key={branch.id}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.branch_ids.includes(
                                                    branch.id,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        'branch_ids',
                                                        branch.id,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {branch.name}
                                        </label>
                                    ))}
                                </div>
                            )}
                            <InputError message={form.errors.branch_ids} />
                        </fieldset>
                        <fieldset className="space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <legend className="text-sm font-medium">
                                    Products
                                </legend>
                                <a
                                    href={createProductUrl}
                                    className="text-primary text-xs font-medium hover:underline"
                                >
                                    Add product
                                </a>
                            </div>
                            {productLocked && context.product ? (
                                <p className="bg-muted rounded-md border px-3 py-2 text-sm font-medium">
                                    {context.product.name}
                                </p>
                            ) : (
                                <div className="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
                                    {context.product_options.map((product) => (
                                        <label
                                            key={product.id}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={form.data.product_ids.includes(
                                                    product.id,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggle(
                                                        'product_ids',
                                                        product.id,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            {product.name}
                                        </label>
                                    ))}
                                </div>
                            )}
                            <InputError message={form.errors.product_ids} />
                        </fieldset>
                    </div>
                    <div className="grid max-w-xs gap-2">
                        <Label htmlFor="quantity">Quantity</Label>
                        <Input
                            id="quantity"
                            type="number"
                            min={1}
                            value={form.data.quantity}
                            onChange={(event) =>
                                form.setData(
                                    'quantity',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <InputError message={form.errors.quantity} />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        Import stock
                    </Button>
                </form>
            </div>
        </>
    );
}
