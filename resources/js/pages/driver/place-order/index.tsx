import { Head } from '@inertiajs/react';
import { Package, UserRound, Wallet } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { PlaceOrderCustomerMode } from '@/components/driver/place-order/place-order-customer-mode';
import {
    PlaceOrderCustomerSection,
    type CustomerFormValues,
} from '@/components/driver/place-order/place-order-customer-section';
import { PlaceOrderPaymentSection } from '@/components/driver/place-order/place-order-payment-section';
import { PlaceOrderProductsSection } from '@/components/driver/place-order/place-order-products-section';
import type {
    CustomerMode,
    DriverPlaceOrderCatalog,
    DriverPlaceOrderCustomer,
} from '@/components/driver/place-order/place-order-types';
import { Button } from '@/components/ui/button';
import { index as placeOrderIndex } from '@/routes/driver/place-order';

const emptyCustomer: CustomerFormValues = {
    name: '',
    phone: '',
    address: '',
    locationLabel: '',
};

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
    customers,
    products,
    paymentMethods,
}: DriverPlaceOrderIndexProps) {
    const [mode, setMode] = useState<CustomerMode>('registered');
    const [customerQuery, setCustomerQuery] = useState('');
    const [selectedCustomerId, setSelectedCustomerId] = useState<string | null>(
        null,
    );
    const [customer, setCustomer] = useState<CustomerFormValues>(emptyCustomer);
    const [quantities, setQuantities] = useState<Record<string, number>>({});
    const [paymentMethodId, setPaymentMethodId] = useState<string | null>(
        paymentMethods[0]?.id ?? null,
    );

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

    const canPlace =
        customer.name.trim() !== '' &&
        customer.phone.trim() !== '' &&
        customer.address.trim() !== '' &&
        paymentMethodId !== null &&
        itemCount > 0;

    const handleModeChange = (next: CustomerMode): void => {
        setMode(next);
        setSelectedCustomerId(null);
        setCustomer(emptyCustomer);
        setCustomerQuery('');
    };

    const handleSelectCustomer = (selected: DriverPlaceOrderCustomer): void => {
        setSelectedCustomerId(selected.id);
        setCustomer({
            name: selected.name,
            phone: selected.phone,
            address: selected.address,
            locationLabel: selected.locationLabel,
        });
    };

    const handlePlaceOrder = (): void => {
        if (!canPlace) {
            toast.message('Complete customer, products, and payment first', {
                description: 'Placeholder — this control is not live yet.',
            });

            return;
        }

        toast.message('Order placed for this demo', {
            description: 'Placeholder — this control is not live yet.',
        });
    };

    return (
        <>
            <Head title="Place order" />

            <div className="flex flex-col gap-5 pb-36 xl:pb-28">
                <div className="space-y-1.5">
                    <h1 className="text-foreground text-2xl font-bold tracking-tight">
                        Place order
                    </h1>
                    <p className="text-muted-foreground text-sm leading-relaxed">
                        Sell branch-assigned stock to a registered customer or
                        add details manually.
                    </p>
                </div>

                <SectionShell
                    icon={UserRound}
                    title="Customer"
                    description="Search a registered shopper, or enter details for a new one."
                >
                    <PlaceOrderCustomerMode
                        mode={mode}
                        onChange={handleModeChange}
                    />
                    <PlaceOrderCustomerSection
                        mode={mode}
                        customers={customers}
                        selectedCustomerId={selectedCustomerId}
                        onSelectCustomer={handleSelectCustomer}
                        onClearSelection={() => {
                            setSelectedCustomerId(null);
                            setCustomer(emptyCustomer);
                        }}
                        values={customer}
                        onChange={(field, value) =>
                            setCustomer((current) => ({
                                ...current,
                                [field]: value,
                            }))
                        }
                        customerQuery={customerQuery}
                        onCustomerQueryChange={setCustomerQuery}
                    />
                </SectionShell>

                <SectionShell
                    icon={Package}
                    title="Products"
                    description="Only in-stock assigned products can be sold."
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
                    description="How this drop will be collected."
                >
                    <PlaceOrderPaymentSection
                        methods={paymentMethods}
                        selectedId={paymentMethodId}
                        onSelect={setPaymentMethodId}
                    />
                </SectionShell>
            </div>

            <div className="border-border/80 bg-background/90 fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 border-t backdrop-blur-md supports-backdrop-filter:bg-background/75 xl:bottom-0">
                <div className="mx-auto flex w-full max-w-3xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
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
                        className="h-12 w-full rounded-2xl sm:w-auto sm:min-w-44"
                        disabled={!canPlace}
                        onClick={handlePlaceOrder}
                    >
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
