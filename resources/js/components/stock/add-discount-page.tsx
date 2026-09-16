import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { StockPairFields } from '@/components/stock/stock-pair-fields';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
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

type DiscountTypeOption = { value: string; label: string };

export function AddDiscountPage({
    context,
    createProductUrl,
    pageUrl,
    updateUrl,
    discountTypes,
    productOptionsUrl,
    branchOptionsUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
    pageUrl: string;
    updateUrl: string;
    discountTypes: DiscountTypeOption[];
    productOptionsUrl: string;
    branchOptionsUrl?: string;
}) {
    const ready = context.branch !== null && context.product !== null;
    const form = useForm({
        branch_id: context.branch?.id ?? 0,
        product_id: context.product?.id ?? 0,
        selling_price: context.price?.selling_price ?? '',
        discount_type: context.price?.discount_type ?? 'none',
        discount_price: context.price?.discount_price ?? '',
        start_date: context.price?.start_date ?? '',
        end_date: context.price?.end_date ?? '',
    });
    const hasDiscount = form.data.discount_type !== 'none';

    return (
        <>
            <Head title="Add discount" />
            <div className="mx-auto max-w-3xl space-y-6 p-2 md:p-4">
                <Heading
                    title="Add discount"
                    description="Discount type, value, and dates for one branch and product"
                />
                <StockPairFields
                    context={context}
                    pageUrl={pageUrl}
                    createProductUrl={createProductUrl}
                    productOptionsUrl={productOptionsUrl}
                    branchOptionsUrl={branchOptionsUrl}
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
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="selling_price">
                                    Selling price
                                </Label>
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
                                <InputError
                                    message={form.errors.selling_price}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="discount_type">
                                    Discount type
                                </Label>
                                <Select
                                    value={form.data.discount_type}
                                    onValueChange={(value) =>
                                        form.setData('discount_type', value)
                                    }
                                >
                                    <SelectTrigger id="discount_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {discountTypes.map((type) => (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.discount_type}
                                />
                            </div>
                        </div>
                        {hasDiscount ? (
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="discount_price">
                                        Discount value
                                    </Label>
                                    <Input
                                        id="discount_price"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        value={form.data.discount_price ?? ''}
                                        onChange={(event) =>
                                            form.setData(
                                                'discount_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.discount_price}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Start date</Label>
                                    <DatePicker
                                        value={form.data.start_date || null}
                                        onChange={(value) =>
                                            form.setData('start_date', value)
                                        }
                                        clearable
                                    />
                                    <InputError
                                        message={form.errors.start_date}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>End date</Label>
                                    <DatePicker
                                        value={form.data.end_date || null}
                                        onChange={(value) =>
                                            form.setData('end_date', value)
                                        }
                                        clearable
                                    />
                                    <InputError
                                        message={form.errors.end_date}
                                    />
                                </div>
                            </div>
                        ) : null}
                        <Button type="submit" disabled={form.processing}>
                            Save discount
                        </Button>
                    </form>
                ) : (
                    <p className="text-muted-foreground text-sm">
                        Select a branch and product to add a discount.
                    </p>
                )}
            </div>
        </>
    );
}
