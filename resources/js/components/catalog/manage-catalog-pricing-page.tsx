import { Head, router, useForm, setLayoutProps } from '@inertiajs/react';
import { Ban, Percent, Tag, type LucideIcon } from 'lucide-react';
import { useEffect, type FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { stockPageUrl } from '@/lib/stock-query';
import { cn } from '@/lib/utils';

export type CatalogPricingProduct = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    image_url: string | null;
    color_code: unknown;
    category_name: string;
    status: boolean;
    price: {
        regular_price: string | number | null;
        selling_price: string | number;
        discount_type: string;
        discount_price: string | number | null;
        start_date: string | null;
        end_date: string | null;
        effective_price: string | null;
        has_discount: boolean;
        has_compare_at: boolean;
        wholesale_price?: string | number | null;
        min_wholesale_qty?: number | null;
    };
};

type DiscountTypeOption = { value: string; label: string };

const DISCOUNT_TYPE_ICONS: Record<string, LucideIcon> = {
    none: Ban,
    fixed: Tag,
    percent: Percent,
};

function formatMoney(value: string | number | null | undefined): string {
    if (value == null || value === '') {
        return '—';
    }

    const amount = Number(value);

    return Number.isFinite(amount) ? amount.toFixed(2) : String(value);
}

function todayDateString(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

function resolveDiscountType(type: string | null | undefined): string {
    if (type === 'fixed' || type === 'percent') {
        return type;
    }

    return 'none';
}

function discountIsLive(data: {
    discount_type: string;
    start_date?: string | null;
    end_date?: string | null;
}): boolean {
    if (resolveDiscountType(data.discount_type) === 'none') {
        return false;
    }

    const today = todayDateString();

    if (data.start_date && today < data.start_date) {
        return false;
    }

    if (data.end_date && today > data.end_date) {
        return false;
    }

    return true;
}

function effectivePrice(data: {
    selling_price: string | number;
    discount_type: string;
    discount_price?: string | number | null;
    start_date?: string | null;
    end_date?: string | null;
}): string {
    const selling = Number(data.selling_price);
    const type = resolveDiscountType(data.discount_type);

    if (
        !discountIsLive(data) ||
        type === 'none' ||
        data.discount_price == null ||
        data.discount_price === '' ||
        !Number.isFinite(selling)
    ) {
        return formatMoney(data.selling_price);
    }

    const discount = Number(data.discount_price);

    if (!Number.isFinite(discount)) {
        return formatMoney(data.selling_price);
    }

    if (type === 'percent') {
        return formatMoney(
            Math.max(0, Math.round(selling * (1 - discount / 100) * 100) / 100),
        );
    }

    return formatMoney(
        Math.max(0, Math.round((selling - discount) * 100) / 100),
    );
}

function SnapshotStat({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint?: string;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-0.5 px-5 py-3">
            <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </p>
            <p className="text-lg font-semibold tracking-tight tabular-nums">
                {value}
            </p>
            {hint ? (
                <p className="text-muted-foreground truncate text-xs">{hint}</p>
            ) : null}
        </div>
    );
}

export function ManageCatalogPricingPage({
    product,
    discountTypes,
    pageUrl,
    productOptionsUrl,
    priceUrl,
    breadcrumbs,
}: {
    product: CatalogPricingProduct | null;
    discountTypes: DiscountTypeOption[];
    pageUrl: string;
    productOptionsUrl: string;
    priceUrl: string | null;
    breadcrumbs: { title: string; href: string }[];
}) {
    setLayoutProps({ breadcrumbs });

    const price = product?.price ?? null;
    const discountType = resolveDiscountType(price?.discount_type);

    const priceForm = useForm({
        regular_price: price?.regular_price ?? '',
        selling_price: price?.selling_price ?? '',
        discount_type: discountType,
        discount_price: price?.discount_price ?? '',
        start_date: price?.start_date ?? '',
        end_date: price?.end_date ?? '',
        wholesale_price: String(price?.wholesale_price ?? ''),
        min_wholesale_qty: String(price?.min_wholesale_qty ?? ''),
    });

    useEffect(() => {
        if (!product) {
            return;
        }

        const next = product.price;
        const nextType = resolveDiscountType(next.discount_type);

        priceForm.setData({
            regular_price: next.regular_price ?? '',
            selling_price: next.selling_price ?? '',
            discount_type: nextType,
            discount_price: next.discount_price ?? '',
            start_date: next.start_date ?? '',
            end_date: next.end_date ?? '',
            wholesale_price: String(next.wholesale_price ?? ''),
            min_wholesale_qty: String(next.min_wholesale_qty ?? ''),
        });
        priceForm.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- remount form when selected product changes
    }, [product?.id]);

    const visitProduct = (productId: number | null) => {
        const target = stockPageUrl(pageUrl, { productId });
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

    const selectDiscountType = (next: string) => {
        priceForm.setData({
            ...priceForm.data,
            discount_type: next,
            discount_price:
                next === 'none' ? '' : priceForm.data.discount_price,
            start_date: next === 'none' ? '' : priceForm.data.start_date,
            end_date: next === 'none' ? '' : priceForm.data.end_date,
        });
    };

    const showDiscountFields = priceForm.data.discount_type !== 'none';
    const sellingDisplay = formatMoney(price?.selling_price);
    const regularDisplay = formatMoney(price?.regular_price);
    const effectiveDisplay = price
        ? effectivePrice({
              selling_price: price.selling_price,
              discount_type: price.discount_type,
              discount_price: price.discount_price,
              start_date: price.start_date,
              end_date: price.end_date,
          })
        : '—';
    const hasLiveDiscount =
        price != null &&
        discountIsLive({
            discount_type: price.discount_type,
            start_date: price.start_date,
            end_date: price.end_date,
        }) &&
        effectiveDisplay !== sellingDisplay;
    const liveEffectiveDisplay = effectivePrice({
        selling_price: priceForm.data.selling_price,
        discount_type: priceForm.data.discount_type,
        discount_price: priceForm.data.discount_price,
        start_date: priceForm.data.start_date,
        end_date: priceForm.data.end_date,
    });

    const submitPrice = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!priceUrl) {
            return;
        }

        priceForm.transform((data) => {
            const none = data.discount_type === 'none';

            return {
                regular_price:
                    data.regular_price === '' ? null : data.regular_price,
                selling_price: data.selling_price,
                discount_type: data.discount_type,
                discount_price: none ? null : data.discount_price,
                start_date:
                    none || data.start_date === '' ? null : data.start_date,
                end_date: none || data.end_date === '' ? null : data.end_date,
                wholesale_price:
                    data.wholesale_price.trim() === ''
                        ? null
                        : data.wholesale_price,
                min_wholesale_qty:
                    data.min_wholesale_qty.trim() === ''
                        ? null
                        : data.min_wholesale_qty,
            };
        });
        priceForm.put(priceUrl, {
            preserveScroll: true,
            onFinish: () => priceForm.transform((data) => data),
        });
    };

    return (
        <>
            <Head title="Manage pricing" />

            <div className="flex w-full min-w-0 flex-col gap-4 p-2 md:p-4">
                <Heading
                    className="min-w-0"
                    title="Manage pricing"
                    description={
                        product
                            ? `Catalog price and discount for ${product.name}`
                            : 'Pick a product to edit regular, selling, and discount.'
                    }
                />

                <section className="border-border/80 bg-card rounded-2xl border p-5 shadow-sm sm:p-6">
                    <div className="space-y-1">
                        <p className="text-sm font-medium">Product</p>
                        <p className="text-muted-foreground text-sm">
                            Prices apply across every branch when branch pricing
                            is off.
                        </p>
                    </div>
                    <div className="mt-4 grid min-w-0 gap-2">
                        <Label htmlFor="catalog-pricing-product">Product</Label>
                        <InfiniteScrollCombobox
                            id="catalog-pricing-product"
                            optionsUrl={productOptionsUrl}
                            value={product ? String(product.id) : null}
                            selectedLabel={product?.name}
                            onValueChange={(value) =>
                                visitProduct(value ? Number(value) : null)
                            }
                            placeholder="Select a product"
                            searchPlaceholder="Search products"
                            emptyText="No products found."
                        />
                    </div>
                </section>

                {product && priceUrl ? (
                    <Card className="overflow-hidden shadow-sm">
                        <CardHeader className="border-b pb-4">
                            <CardTitle className="text-base">
                                Price & discount
                            </CardTitle>
                            <CardDescription>
                                Regular is compare-at. Selling is the ticket
                                before a catalog discount.
                            </CardDescription>
                        </CardHeader>

                        <div className="grid divide-y border-b sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                            <SnapshotStat
                                label="Selling"
                                value={sellingDisplay}
                                hint={price ? 'AUD' : 'No price on file'}
                            />
                            <SnapshotStat
                                label="Effective"
                                value={effectiveDisplay}
                                hint={
                                    hasLiveDiscount
                                        ? 'After discount'
                                        : 'Same as selling'
                                }
                            />
                            <SnapshotStat
                                label="Regular"
                                value={regularDisplay}
                                hint={
                                    price?.has_compare_at
                                        ? 'Compare-at'
                                        : 'Optional'
                                }
                            />
                        </div>

                        <form className="border-b p-5" onSubmit={submitPrice}>
                            <div className="flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <h3 className="text-sm font-semibold">
                                        Price
                                    </h3>
                                    <p className="text-muted-foreground text-xs">
                                        Regular, selling and wholesale price
                                    </p>
                                </div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={priceForm.processing}
                                >
                                    Save
                                </Button>
                            </div>

                            <div className="mt-4 grid min-w-0 items-start gap-4 sm:grid-cols-2">
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="catalog_regular_price">
                                        Regular price{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (AUD)
                                        </span>
                                    </Label>
                                    <Input
                                        id="catalog_regular_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        placeholder="e.g. 19.99"
                                        value={priceForm.data.regular_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'regular_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Compare-at / was. Shown when higher than
                                        selling.
                                    </p>
                                    <InputError
                                        message={priceForm.errors.regular_price}
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="catalog_selling_price">
                                        Selling price{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (AUD)
                                        </span>
                                    </Label>
                                    <Input
                                        id="catalog_selling_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        placeholder="e.g. 16.50"
                                        value={priceForm.data.selling_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'selling_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Ticket price before a catalog discount.
                                    </p>
                                    <InputError
                                        message={priceForm.errors.selling_price}
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="catalog_wholesale_price">
                                        Wholesale price{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (AUD)
                                        </span>
                                    </Label>
                                    <Input
                                        id="catalog_wholesale_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        placeholder="Selling price"
                                        value={priceForm.data.wholesale_price}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'wholesale_price',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Unit price suggested for wholesale
                                        orders. Empty uses selling.
                                    </p>
                                    <InputError
                                        message={
                                            priceForm.errors.wholesale_price
                                        }
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label htmlFor="catalog_min_wholesale_qty">
                                        Wholesale min qty
                                    </Label>
                                    <Input
                                        id="catalog_min_wholesale_qty"
                                        type="number"
                                        step="1"
                                        min={1}
                                        placeholder="No minimum"
                                        value={priceForm.data.min_wholesale_qty}
                                        onChange={(event) =>
                                            priceForm.setData(
                                                'min_wholesale_qty',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        Smallest quantity a wholesale request
                                        may ask for.
                                    </p>
                                    <InputError
                                        message={
                                            priceForm.errors.min_wholesale_qty
                                        }
                                    />
                                </div>
                            </div>
                        </form>

                        <form className="p-5" onSubmit={submitPrice}>
                            <div className="bg-muted/40 grid min-w-0 gap-4 rounded-lg border p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <h4 className="text-sm font-medium">
                                            Discount
                                        </h4>
                                        <p className="text-muted-foreground text-xs">
                                            Applies to selling only. Empty dates
                                            keep the offer always on.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <p className="text-muted-foreground text-xs tabular-nums">
                                            Shoppers pay{' '}
                                            <span className="text-foreground font-medium">
                                                {liveEffectiveDisplay}
                                            </span>{' '}
                                            AUD
                                        </p>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={priceForm.processing}
                                        >
                                            Save
                                        </Button>
                                    </div>
                                </div>

                                <div className="grid max-w-xl gap-2">
                                    <Label id="catalog_discount_type_label">
                                        Type
                                    </Label>
                                    <div
                                        role="group"
                                        aria-labelledby="catalog_discount_type_label"
                                        className="grid grid-cols-3 gap-2"
                                    >
                                        {discountTypes.map((type) => {
                                            const Icon =
                                                DISCOUNT_TYPE_ICONS[
                                                    type.value
                                                ] ?? Tag;
                                            const selected =
                                                priceForm.data.discount_type ===
                                                type.value;

                                            return (
                                                <Button
                                                    key={type.value}
                                                    type="button"
                                                    variant={
                                                        selected
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    className={cn(
                                                        'h-auto min-h-9 flex-col gap-1 px-1 py-2 text-[11px] sm:h-9 sm:flex-row sm:gap-1.5 sm:px-2 sm:py-0 sm:text-xs',
                                                        !selected &&
                                                            'text-foreground',
                                                    )}
                                                    onClick={() =>
                                                        selectDiscountType(
                                                            type.value,
                                                        )
                                                    }
                                                >
                                                    <Icon className="size-3.5" />
                                                    {type.label}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                    <InputError
                                        message={priceForm.errors.discount_type}
                                    />
                                </div>

                                {showDiscountFields ? (
                                    <div className="grid min-w-0 items-start gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div className="grid min-w-0 gap-2">
                                            <Label htmlFor="catalog_discount_price">
                                                Discount value{' '}
                                                <span className="text-muted-foreground font-normal">
                                                    {priceForm.data
                                                        .discount_type ===
                                                    'percent'
                                                        ? '(%)'
                                                        : '(AUD)'}
                                                </span>
                                            </Label>
                                            <Input
                                                id="catalog_discount_price"
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                max={
                                                    priceForm.data
                                                        .discount_type ===
                                                    'percent'
                                                        ? 100
                                                        : priceForm.data
                                                              .selling_price ||
                                                          undefined
                                                }
                                                placeholder={
                                                    priceForm.data
                                                        .discount_type ===
                                                    'percent'
                                                        ? 'e.g. 10'
                                                        : 'e.g. 5.00'
                                                }
                                                value={
                                                    priceForm.data
                                                        .discount_price
                                                }
                                                onChange={(event) =>
                                                    priceForm.setData(
                                                        'discount_price',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <p className="text-muted-foreground text-xs">
                                                {priceForm.data
                                                    .discount_type === 'percent'
                                                    ? 'Off the selling price (0–100).'
                                                    : 'Amount off selling, ≤ selling.'}
                                            </p>
                                            <InputError
                                                message={
                                                    priceForm.errors
                                                        .discount_price
                                                }
                                            />
                                        </div>
                                        <div className="grid min-w-0 gap-2">
                                            <Label htmlFor="catalog_start_date">
                                                Start date
                                            </Label>
                                            <DatePicker
                                                id="catalog_start_date"
                                                value={
                                                    priceForm.data.start_date
                                                }
                                                onChange={(next) =>
                                                    priceForm.setData(
                                                        'start_date',
                                                        next,
                                                    )
                                                }
                                                max={
                                                    priceForm.data.end_date ||
                                                    undefined
                                                }
                                                placeholder="Optional start"
                                                aria-invalid={Boolean(
                                                    priceForm.errors.start_date,
                                                )}
                                            />
                                            <InputError
                                                message={
                                                    priceForm.errors.start_date
                                                }
                                            />
                                        </div>
                                        <div className="grid min-w-0 gap-2">
                                            <Label htmlFor="catalog_end_date">
                                                End date
                                            </Label>
                                            <DatePicker
                                                id="catalog_end_date"
                                                value={priceForm.data.end_date}
                                                onChange={(next) =>
                                                    priceForm.setData(
                                                        'end_date',
                                                        next,
                                                    )
                                                }
                                                min={
                                                    priceForm.data.start_date ||
                                                    undefined
                                                }
                                                placeholder="Optional end"
                                                aria-invalid={Boolean(
                                                    priceForm.errors.end_date,
                                                )}
                                            />
                                            <InputError
                                                message={
                                                    priceForm.errors.end_date
                                                }
                                            />
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        </form>
                    </Card>
                ) : (
                    <div className="border-border/80 text-muted-foreground rounded-2xl border border-dashed p-8 text-center text-sm">
                        Select a product to edit catalog pricing.
                    </div>
                )}
            </div>
        </>
    );
}
