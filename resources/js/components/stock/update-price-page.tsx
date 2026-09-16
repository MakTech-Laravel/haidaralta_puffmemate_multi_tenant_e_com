import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { StockPageContext } from '@/lib/stock-query';

export function UpdatePricePage({
    context,
    createProductUrl,
    pageUrl,
    updateUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
    pageUrl: string;
    updateUrl: string;
}) {
    const ready = context.branch !== null && context.product !== null;
    const form = useForm({
        branch_id: context.branch?.id ?? 0,
        product_id: context.product?.id ?? 0,
        selling_price: context.price?.selling_price ?? '',
    });

    return (
        <>
            <Head title="Update price" />
            <div className="mx-auto max-w-3xl space-y-6 p-2 md:p-4">
                <Heading
                    title="Update price"
                    description="Set the selling price for one branch and product"
                />
                <StockPairFields
                    context={context}
                    pageUrl={pageUrl}
                    createProductUrl={createProductUrl}
                />
                {ready ? (
                    <form
                        className="space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.transform((data) => ({
                                ...data,
                                branch_id: context.branch?.id ?? 0,
                                product_id: context.product?.id ?? 0,
                            }));
                            form.put(updateUrl, { preserveScroll: true });
                        }}
                    >
                        <div className="grid max-w-xs gap-2">
                            <Label htmlFor="selling_price">Selling price</Label>
                            <Input
                                id="selling_price"
                                type="number"
                                min={0}
                                step="0.01"
                                value={form.data.selling_price}
                                onChange={(event) =>
                                    form.setData(
                                        'selling_price',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={form.errors.selling_price} />
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            Save price
                        </Button>
                    </form>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Select a branch and product to update the price.
                    </p>
                )}
            </div>
        </>
    );
}
