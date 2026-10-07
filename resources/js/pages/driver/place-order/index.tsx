import { Head, router } from '@inertiajs/react';
import { Package, UserRound, Wallet } from 'lucide-react';
import { useMemo, useRef, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { PlaceOrderCustomerSection } from '@/components/driver/place-order/place-order-customer-section';
import { PlaceOrderPaymentSection } from '@/components/driver/place-order/place-order-payment-section';
import { PlaceOrderProductsSection } from '@/components/driver/place-order/place-order-products-section';
import type {
    DriverPlaceOrderCatalog,
    ResolvedPlaceOrderCustomer,
} from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/App/Http/Controllers/Driver/PlaceOrderController';
import { index as placeOrderIndex } from '@/routes/driver/place-order';

type DriverPlaceOrderIndexProps = DriverPlaceOrderCatalog;

function formatMoney(amount: number): string {
    return `$${amount.toFixed(2)}`;
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
        <section className="border-border/80 bg-card/80 space-y-4 rounded-2xl border p-4 shadow-sm">
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

export default function DriverPlaceOrderIndex({
    products,
    paymentMethods,
    canSell,
}: DriverPlaceOrderIndexProps) {
    const [phone, setPhone] = useState('');
    const [name, setName] = useState('');
    const [customer, setCustomer] = useState<ResolvedPlaceOrderCustomer | null>(
        null,
    );
    const [needsAdd, setNeedsAdd] = useState(false);
    const [quantities, setQuantities] = useState<Record<string, number>>({});
    const [paymentMethodId, setPaymentMethodId] = useState<string | null>(
        paymentMethods[0]?.id ?? null,
    );
    const [processing, setProcessing] = useState(false);
    const idempotencyKey = useRef(crypto.randomUUID());

    const lineItems = useMemo(
        () =>
            products
                .map((product) => {
                    const quantity = quantities[product.id] ?? 0;

                    if (quantity < 1 || product.stock < 1) {
                        return null;
                    }

                    return {
                        product,
                        quantity,
                        lineTotal: product.price * quantity,
                    };
                })
                .filter((item) => item !== null),
        [products, quantities],
    );

    const total = lineItems.reduce((sum, item) => sum + item.lineTotal, 0);
    const itemCount = lineItems.reduce((sum, item) => sum + item.quantity, 0);
    const customerReady =
        customer !== null ||
        (needsAdd && name.trim() !== '' && phone.trim() !== '');

    const canPlace =
        canSell &&
        customerReady &&
        paymentMethodId !== null &&
        itemCount > 0 &&
        !processing;

    const handlePlaceOrder = (): void => {
        if (!canPlace || paymentMethodId === null) {
            return;
        }

        router.post(
            store.url(),
            {
                customer_id: customer?.id ?? null,
                phone: customer?.phone ?? phone,
                name: customer?.name ?? name,
                payment_method: paymentMethodId,
                idempotency_key: idempotencyKey.current,
                items: lineItems.map((item) => ({
                    id: item.product.id,
                    quantity: item.quantity,
                })),
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => {
                    const message = Object.values(errors)[0];
                    toast.error(
                        typeof message === 'string'
                            ? message
                            : 'The sale could not be placed.',
                    );
                },
            },
        );
    };

    return (
        <>
            <Head title="Place order" />

            <div className="flex flex-col gap-5 pb-4">
                <div className="space-y-1.5">
                    <h1 className="text-foreground text-2xl font-bold tracking-tight">
                        Place order
                    </h1>
                    <p className="text-muted-foreground text-sm leading-relaxed">
                        Search a customer by name, email, or phone and sell
                        stock from your active branch.
                    </p>
                </div>

                {!canSell ? (
                    <p className="text-destructive text-sm">
                        You need one active branch assignment before selling.
                    </p>
                ) : null}

                <SectionShell
                    icon={UserRound}
                    title="Customer"
                    description="Auto-search by name, email, or phone. Add details only when nothing matches."
                >
                    <PlaceOrderCustomerSection
                        phone={phone}
                        name={name}
                        customer={customer}
                        needsAdd={needsAdd}
                        onPhoneChange={setPhone}
                        onNameChange={setName}
                        onResolved={(resolved, askToAdd) => {
                            setCustomer(resolved);
                            setNeedsAdd(askToAdd);
                        }}
                    />
                </SectionShell>

                <SectionShell
                    icon={Package}
                    title="Products"
                    description="Browse your assigned stock. Search and category chips filter the list."
                >
                    <PlaceOrderProductsSection
                        products={products}
                        quantities={quantities}
                        onQuantityChange={(productId, quantity) =>
                            setQuantities((current) => ({
                                ...current,
                                [productId]: quantity,
                            }))
                        }
                    />
                </SectionShell>

                <SectionShell
                    icon={Wallet}
                    title="Payment"
                    description="How this sale will be collected."
                >
                    <PlaceOrderPaymentSection
                        methods={paymentMethods}
                        selectedId={paymentMethodId}
                        onSelect={setPaymentMethodId}
                    />
                </SectionShell>

                <div className="border-border/80 bg-background/95 sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 -mx-1 flex items-center justify-between gap-4 rounded-2xl border px-4 py-3 shadow-sm backdrop-blur-md supports-backdrop-filter:bg-background/85 xl:bottom-0">
                    <div className="min-w-0">
                        <p className="text-muted-foreground text-xs font-medium">
                            {itemCount} {itemCount === 1 ? 'item' : 'items'}
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
                        {processing ? <Spinner /> : null}
                        Place order
                    </Button>
                </div>
            </div>
        </>
    );
}

DriverPlaceOrderIndex.layout = {
    breadcrumbs: [
        {
            title: 'Place order',
            href: placeOrderIndex.url(),
        },
    ],
};
