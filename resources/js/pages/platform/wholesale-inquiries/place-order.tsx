import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Building2, CreditCard, Package, Truck, UserRound } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
import WholesaleOrderController from '@/actions/App/Http/Controllers/Platform/WholesaleOrderController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    PaymentMethodOptions,
    type PaymentMethodOption,
} from '@/components/orders/payment-method-options';
import { PlaceOrderProductsSection } from '@/components/platform/wholesale/place-order-products-section';
import type {
    WholesalePlaceOrderBranch,
    WholesalePlaceOrderCustomerForm,
    WholesalePlaceOrderInquiry,
    WholesalePlaceOrderLine,
    WholesalePlaceOrderProduct,
} from '@/components/platform/wholesale/place-order-types';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { CheckoutMapDialog } from '@/components/storefront/checkout/checkout-map-dialog';
import { AddressSuggestInput } from '@/components/storefront/wholesale/address-suggest-input';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import { Textarea } from '@/components/ui/textarea';

const ANY_SLOT = 'any';
/** Matches StoreWholesaleOrderRequest `notes` max. */
const NOTE_MAX_LENGTH = 1000;

function formatMoney(amount: number): string {
    return `$${amount.toFixed(2)}`;
}

function toAmount(value: string): number {
    const amount = Number(value);

    return Number.isFinite(amount) && amount > 0 ? amount : 0;
}

/** Re-prices lines against the selected branch's stock, keeping typed prices. */
function syncLines(
    lines: WholesalePlaceOrderLine[],
    products: WholesalePlaceOrderProduct[],
): WholesalePlaceOrderLine[] {
    return lines.map((line) => {
        const product = products.find((item) => item.id === line.key);

        return {
            ...line,
            name: product?.name ?? line.name,
            sku: product?.sku ?? line.sku,
            stock: product?.stock ?? 0,
            minQuantity: product?.min_quantity ?? line.minQuantity ?? null,
            price: line.priceEdited
                ? line.price
                : (product?.price ?? line.price),
        };
    });
}

function inquiryLines(
    inquiry: WholesalePlaceOrderInquiry | null,
): WholesalePlaceOrderLine[] {
    return (inquiry?.items ?? []).map((item) => ({
        key:
            item.variant_id === null
                ? String(item.product_id)
                : `${item.product_id}:${item.variant_id}`,
        productId: String(item.product_id),
        variantId: item.variant_id,
        name: item.name,
        sku: item.sku ?? '',
        price: 0,
        stock: 0,
        quantity: item.quantity,
        minQuantity: null,
    }));
}

function SectionShell({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: typeof UserRound;
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className="border-border/80 bg-card space-y-4 rounded-2xl border p-4 shadow-sm sm:p-5">
            <div className="flex items-start gap-3">
                <span className="bg-muted text-foreground flex size-9 shrink-0 items-center justify-center rounded-xl">
                    <Icon className="size-4" aria-hidden />
                </span>
                <div className="min-w-0 space-y-0.5">
                    <h2 className="text-foreground text-base font-bold tracking-tight">
                        {title}
                    </h2>
                    {description ? (
                        <p className="text-muted-foreground text-xs leading-relaxed">
                            {description}
                        </p>
                    ) : null}
                </div>
            </div>
            {children}
        </section>
    );
}

export default function PlatformWholesalePlaceOrder({
    inquiry,
    branch,
    branchOptionsUrl,
    deliveryQuoteUrl,
    products,
    paymentMethods,
    timeSlots,
}: {
    inquiry: WholesalePlaceOrderInquiry | null;
    branch: WholesalePlaceOrderBranch | null;
    branchOptionsUrl: string;
    deliveryQuoteUrl: string;
    products: WholesalePlaceOrderProduct[];
    paymentMethods: PaymentMethodOption[];
    timeSlots: string[];
}) {
    const [customer, setCustomer] = useState<WholesalePlaceOrderCustomerForm>({
        full_name: inquiry?.full_name ?? '',
        phone: inquiry?.phone ?? '',
        email: inquiry?.email ?? '',
        company: inquiry?.company ?? '',
        address: inquiry?.address ?? '',
        latitude: inquiry?.latitude ?? '',
        longitude: inquiry?.longitude ?? '',
        note: (inquiry?.message ?? '').slice(0, NOTE_MAX_LENGTH),
    });
    const [lines, setLines] = useState<WholesalePlaceOrderLine[]>(() =>
        syncLines(inquiryLines(inquiry), products),
    );
    const [discount, setDiscount] = useState('');
    const [deliveryFee, setDeliveryFee] = useState('');
    const [feeEdited, setFeeEdited] = useState(false);
    const [quoting, setQuoting] = useState(false);
    const [paymentMethodId, setPaymentMethodId] = useState<string | null>(
        paymentMethods[0]?.id ?? null,
    );
    const [deliveryDate, setDeliveryDate] = useState('');
    const [timeSlot, setTimeSlot] = useState(ANY_SLOT);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const idempotencyKey = useRef(crypto.randomUUID());

    useEffect(() => {
        setLines((current) => syncLines(current, products));
    }, [products]);

    useEffect(() => {
        if (
            feeEdited ||
            branch === null ||
            customer.latitude.trim() === '' ||
            customer.longitude.trim() === ''
        ) {
            return;
        }

        const controller = new AbortController();
        const url = new URL(deliveryQuoteUrl, window.location.origin);
        url.searchParams.set('branch', String(branch.id));
        url.searchParams.set('latitude', customer.latitude);
        url.searchParams.set('longitude', customer.longitude);

        setQuoting(true);
        fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((quote: { fee?: number } | null) => {
                if (quote && typeof quote.fee === 'number') {
                    setDeliveryFee(quote.fee.toFixed(2));
                }
            })
            .catch(() => undefined)
            .finally(() => {
                if (!controller.signal.aborted) {
                    setQuoting(false);
                }
            });

        return () => controller.abort();
    }, [
        branch,
        customer.latitude,
        customer.longitude,
        deliveryQuoteUrl,
        feeEdited,
    ]);

    const subtotal = lines.reduce(
        (sum, line) => sum + line.price * line.quantity,
        0,
    );
    const discountAmount = Math.min(toAmount(discount), subtotal);
    const feeAmount = toAmount(deliveryFee);
    const total = Math.max(subtotal - discountAmount + feeAmount, 0);
    const itemCount = lines.reduce((sum, line) => sum + line.quantity, 0);
    const shortLines = lines.filter((line) => line.quantity > line.stock);

    const lineErrors = Object.fromEntries(
        lines.map((line, index) => [
            line.key,
            errors[`items.${index}.quantity`] ??
                errors[`items.${index}.product_id`] ??
                errors[`items.${index}.unit_price`],
        ]),
    );

    const canPlace =
        branch !== null &&
        lines.length > 0 &&
        shortLines.length === 0 &&
        paymentMethodId !== null &&
        customer.full_name.trim() !== '' &&
        customer.phone.trim() !== '' &&
        customer.address.trim() !== '' &&
        customer.latitude.trim() !== '' &&
        customer.longitude.trim() !== '' &&
        !processing;

    const setCustomerField = (
        field: keyof WholesalePlaceOrderCustomerForm,
        value: string,
    ): void => {
        setCustomer((current) => ({ ...current, [field]: value }));
    };

    const selectBranch = (value: string | null): void => {
        const url =
            inquiry === null
                ? WholesaleInquiryController.createPlaceOrder.url()
                : WholesaleInquiryController.placeOrder.url(inquiry.id);

        router.get(url, value === null ? {} : { branch: value }, {
            only: ['branch', 'products'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const addProduct = (product: WholesalePlaceOrderProduct): void => {
        setLines((current) => {
            if (current.some((line) => line.key === product.id)) {
                return current;
            }

            return [
                ...current,
                {
                    key: product.id,
                    productId: String(product.product_id),
                    variantId: product.variant_id,
                    name: product.name,
                    sku: product.sku,
                    price: product.price,
                    stock: product.stock,
                    minQuantity: product.min_quantity,
                    quantity: Math.min(
                        product.stock,
                        Math.max(1, product.min_quantity ?? 1),
                    ),
                },
            ];
        });
    };

    const updateLineQuantity = (key: string, quantity: number): void => {
        setLines((current) =>
            current.map((line) => {
                if (line.key !== key) {
                    return line;
                }

                return {
                    ...line,
                    quantity: Math.max(
                        1,
                        Math.min(Math.max(line.stock, 1), Math.trunc(quantity)),
                    ),
                };
            }),
        );
    };

    const updateLinePrice = (key: string, price: number): void => {
        setLines((current) =>
            current.map((line) =>
                line.key === key
                    ? {
                          ...line,
                          price: Number.isFinite(price)
                              ? Math.max(0, price)
                              : 0,
                          priceEdited: true,
                      }
                    : line,
            ),
        );
    };

    const removeLine = (key: string): void => {
        setLines((current) => current.filter((line) => line.key !== key));
    };

    const handlePlaceOrder = (): void => {
        if (!canPlace || branch === null || paymentMethodId === null) {
            return;
        }

        router.post(
            WholesaleOrderController.store.url(),
            {
                wholesale_inquiry_id: inquiry?.id ?? null,
                customer_id: inquiry?.customer?.id ?? null,
                name: customer.full_name,
                phone: customer.phone,
                email: customer.email,
                company: customer.company,
                address: customer.address,
                latitude: customer.latitude,
                longitude: customer.longitude,
                branch_id: branch.id,
                items: lines.map((line) => ({
                    product_id: Number(line.productId),
                    variant_id: line.variantId,
                    quantity: line.quantity,
                    unit_price: line.price,
                })),
                discount: discount === '' ? null : discount,
                delivery_fee: deliveryFee === '' ? null : deliveryFee,
                payment_method: paymentMethodId,
                delivery_date: deliveryDate,
                time_slot: timeSlot === ANY_SLOT ? null : timeSlot,
                notes: customer.note,
                idempotency_key: idempotencyKey.current,
            },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);
                    setErrors({});
                },
                onFinish: () => setProcessing(false),
                onError: (nextErrors) => {
                    setErrors(nextErrors);
                    const message = Object.values(nextErrors)[0];
                    toast.error(
                        typeof message === 'string'
                            ? message
                            : 'The order could not be placed.',
                    );
                },
            },
        );
    };

    return (
        <>
            <Head
                title={
                    inquiry
                        ? `Place order · Inquiry #${inquiry.id}`
                        : 'Place order'
                }
            />

            <div className="flex w-full max-w-full min-w-0 flex-col gap-4 p-2 pb-28 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Place order"
                        description={
                            inquiry
                                ? `From wholesale inquiry #${inquiry.id} · ${inquiry.full_name}`
                                : 'Enter customer details, choose a branch, and add products.'
                        }
                    />
                    <Button variant="secondary" asChild>
                        <Link
                            href={
                                inquiry
                                    ? WholesaleInquiryController.show.url(
                                          inquiry.id,
                                      )
                                    : WholesaleInquiryController.index.url()
                            }
                        >
                            Back
                        </Link>
                    </Button>
                </div>

                <SectionShell
                    icon={UserRound}
                    title="Customer"
                    description={
                        inquiry
                            ? 'Prefills from the inquiry — edit before placing the order.'
                            : 'Fill in customer details before placing the order.'
                    }
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="wo-full-name">Full name</Label>
                            <Input
                                id="wo-full-name"
                                value={customer.full_name}
                                onChange={(event) =>
                                    setCustomerField(
                                        'full_name',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="wo-phone">Phone</Label>
                            <Input
                                id="wo-phone"
                                type="tel"
                                value={customer.phone}
                                onChange={(event) =>
                                    setCustomerField(
                                        'phone',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="wo-email">Email</Label>
                            <Input
                                id="wo-email"
                                type="email"
                                value={customer.email}
                                onChange={(event) =>
                                    setCustomerField(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.email} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="wo-company">Company</Label>
                            <Input
                                id="wo-company"
                                value={customer.company}
                                onChange={(event) =>
                                    setCustomerField(
                                        'company',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.company} />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <div className="flex items-end gap-2">
                                <div className="min-w-0 flex-1 space-y-2">
                                    <Label htmlFor="wo-address">Address</Label>
                                    <AddressSuggestInput
                                        id="wo-address"
                                        name="address"
                                        value={customer.address}
                                        placeholder="Start typing and select an address"
                                        onSelect={({
                                            address,
                                            latitude,
                                            longitude,
                                        }) => {
                                            setCustomer((current) => ({
                                                ...current,
                                                address,
                                                latitude,
                                                longitude,
                                            }));
                                        }}
                                        onClear={() => {
                                            setCustomer((current) => ({
                                                ...current,
                                                address: '',
                                                latitude: '',
                                                longitude: '',
                                            }));
                                        }}
                                    />
                                </div>
                                <CheckoutMapDialog
                                    latitude={customer.latitude}
                                    longitude={customer.longitude}
                                    onSelect={({
                                        latitude,
                                        longitude,
                                        address,
                                    }) => {
                                        setCustomer((current) => ({
                                            ...current,
                                            latitude,
                                            longitude,
                                            address: address || current.address,
                                        }));
                                    }}
                                />
                            </div>
                            <InputError message={errors.address} />
                            <InputError
                                message={errors.latitude ?? errors.longitude}
                            />
                            {!customer.latitude || !customer.longitude ? (
                                <Alert variant="destructive">
                                    <AlertDescription>
                                        Select an address from the suggestions
                                        or pin the location on the map before
                                        placing the order.
                                    </AlertDescription>
                                </Alert>
                            ) : null}
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="wo-note">Note</Label>
                            <Textarea
                                id="wo-note"
                                rows={3}
                                maxLength={NOTE_MAX_LENGTH}
                                value={customer.note}
                                onChange={(event) =>
                                    setCustomerField('note', event.target.value)
                                }
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </div>
                </SectionShell>

                <SectionShell
                    icon={Building2}
                    title="Branch"
                    description="Choose which branch stock this order pulls from."
                >
                    <div className="grid max-w-sm gap-2">
                        <Label htmlFor="wo-branch">Branch</Label>
                        <InfiniteScrollCombobox
                            id="wo-branch"
                            optionsUrl={branchOptionsUrl}
                            value={branch !== null ? String(branch.id) : null}
                            selectedLabel={branch?.name ?? null}
                            onValueChange={selectBranch}
                            placeholder="Select a branch"
                            searchPlaceholder="Search branches…"
                            emptyText="No branches found."
                            loadErrorText="Could not load branches."
                            noneLabel="Select a branch"
                            descriptionKey={null}
                            className="h-12 rounded-2xl"
                        />
                        <InputError message={errors.branch_id} />
                    </div>
                </SectionShell>

                <div className="space-y-3">
                    <div className="flex items-start gap-3 px-0.5">
                        <span className="bg-muted text-foreground flex size-9 shrink-0 items-center justify-center rounded-xl">
                            <Package className="size-4" aria-hidden />
                        </span>
                        <div className="min-w-0 space-y-0.5">
                            <h2 className="text-foreground text-base font-bold tracking-tight">
                                Products
                            </h2>
                            <p className="text-muted-foreground text-xs leading-relaxed">
                                Available stock from the selected branch. Prices
                                default to the wholesale price and can be
                                adjusted per line.
                            </p>
                        </div>
                    </div>
                    {branch !== null && shortLines.length > 0 ? (
                        <Alert variant="destructive">
                            <AlertDescription>
                                {shortLines.length === 1
                                    ? `${shortLines[0].name} does not have enough stock at ${branch.name}.`
                                    : `${shortLines.length} products do not have enough stock at ${branch.name}.`}{' '}
                                Lower the quantity, remove the line, or choose
                                another branch.
                            </AlertDescription>
                        </Alert>
                    ) : null}
                    <PlaceOrderProductsSection
                        products={products}
                        lines={lines}
                        disabled={branch === null}
                        onAdd={addProduct}
                        onQuantityChange={updateLineQuantity}
                        onPriceChange={updateLinePrice}
                        onRemove={removeLine}
                        lineErrors={lineErrors}
                    />
                    <InputError message={errors.items} />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <SectionShell
                        icon={Truck}
                        title="Delivery"
                        description="Fee defaults to the branch's distance-based shipping rule."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="wo-delivery-fee">
                                    Delivery fee (AUD)
                                </Label>
                                <Input
                                    id="wo-delivery-fee"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min={0}
                                    value={deliveryFee}
                                    placeholder={
                                        quoting ? 'Calculating…' : '0.00'
                                    }
                                    onChange={(event) => {
                                        setFeeEdited(true);
                                        setDeliveryFee(event.target.value);
                                    }}
                                />
                                {feeEdited ? (
                                    <button
                                        type="button"
                                        className="text-primary justify-self-start text-xs font-medium hover:underline"
                                        onClick={() => setFeeEdited(false)}
                                    >
                                        Recalculate from distance
                                    </button>
                                ) : null}
                                <InputError message={errors.delivery_fee} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="wo-discount">
                                    Discount (AUD)
                                </Label>
                                <Input
                                    id="wo-discount"
                                    type="number"
                                    inputMode="decimal"
                                    step="0.01"
                                    min={0}
                                    value={discount}
                                    placeholder="0.00"
                                    onChange={(event) =>
                                        setDiscount(event.target.value)
                                    }
                                />
                                <InputError message={errors.discount} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="wo-delivery-date">
                                    Delivery date
                                </Label>
                                <DatePicker
                                    id="wo-delivery-date"
                                    value={deliveryDate}
                                    onChange={setDeliveryDate}
                                    min={format(new Date(), 'yyyy-MM-dd')}
                                    placeholder="Any date"
                                    aria-invalid={Boolean(errors.delivery_date)}
                                />
                                <InputError message={errors.delivery_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="wo-time-slot">Time slot</Label>
                                <Select
                                    value={timeSlot}
                                    onValueChange={setTimeSlot}
                                >
                                    <SelectTrigger id="wo-time-slot">
                                        <SelectValue placeholder="Any time" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ANY_SLOT}>
                                            Any time
                                        </SelectItem>
                                        {timeSlots.map((slot) => (
                                            <SelectItem key={slot} value={slot}>
                                                {slot}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.time_slot} />
                            </div>
                        </div>
                    </SectionShell>

                    <SectionShell
                        icon={CreditCard}
                        title="Payment"
                        description="Collected by the driver on delivery."
                    >
                        {paymentMethods.length === 0 ? (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    No payment methods are enabled. Enable one
                                    in application settings first.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <PaymentMethodOptions
                                methods={paymentMethods}
                                selectedId={paymentMethodId}
                                onSelect={setPaymentMethodId}
                            />
                        )}
                        <InputError message={errors.payment_method} />
                    </SectionShell>
                </div>

                <div className="border-border/80 bg-background/95 supports-backdrop-filter:bg-background/85 sticky bottom-0 z-20 -mx-1 flex flex-wrap items-center justify-between gap-4 rounded-2xl border px-4 py-3 shadow-sm backdrop-blur-md">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-xs font-medium">
                            {itemCount} {itemCount === 1 ? 'item' : 'items'} ·
                            Subtotal {formatMoney(subtotal)}
                            {discountAmount > 0
                                ? ` · Discount −${formatMoney(discountAmount)}`
                                : ''}{' '}
                            · Delivery {formatMoney(feeAmount)}
                        </p>
                        <p className="text-foreground text-2xl font-bold tracking-tight tabular-nums">
                            {formatMoney(total)}
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="lg"
                        className="h-12 shrink-0 rounded-2xl px-6 sm:min-w-44"
                        disabled={!canPlace}
                        onClick={handlePlaceOrder}
                    >
                        {processing ? 'Placing…' : 'Place order'}
                    </Button>
                </div>
            </div>
        </>
    );
}

PlatformWholesalePlaceOrder.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Wholesale inquiries',
            href: '/platform/wholesale-inquiries',
        },
        { title: 'Place order', href: '#' },
    ],
};
