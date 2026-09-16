import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { StockPageContext } from '@/lib/stock-query';

const ADJUST_TYPES = [
    { value: 'stock_in', label: 'New entry' },
    { value: 'stock_out', label: 'Reduce' },
    { value: 'adjustment', label: 'Adjustment' },
];

export function ManageStockPage({
    context,
    createProductUrl,
    pageUrl,
    adjustUrl,
    title = 'Manage stock',
}: {
    context: StockPageContext;
    createProductUrl: string;
    pageUrl: string;
    adjustUrl: string;
    title?: string;
}) {
    const ready = context.branch !== null && context.product !== null;
    const form = useForm({
        branch_id: context.branch?.id ?? 0,
        product_id: context.product?.id ?? 0,
        type: 'stock_in',
        quantity: 0,
    });

    return (
        <>
            <Head title={title} />
            <div className="mx-auto max-w-3xl space-y-6 p-2 md:p-4">
                <Heading
                    title={title}
                    description="Adjust stock for one branch and one product"
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
                            form.post(adjustUrl, { preserveScroll: true });
                        }}
                    >
                        <p className="text-muted-foreground text-sm">
                            Current stock:{' '}
                            <span className="text-foreground font-medium tabular-nums">
                                {context.inventory?.quantity ?? 0}
                            </span>
                        </p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="type">Adjustment</Label>
                                <Select
                                    value={form.data.type}
                                    onValueChange={(value) =>
                                        form.setData('type', value)
                                    }
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ADJUST_TYPES.map((type) => (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.type} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="quantity">Quantity</Label>
                                <Input
                                    id="quantity"
                                    type="number"
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
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            Save stock
                        </Button>
                    </form>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Select a branch and product to adjust stock.
                    </p>
                )}
            </div>
        </>
    );
}
