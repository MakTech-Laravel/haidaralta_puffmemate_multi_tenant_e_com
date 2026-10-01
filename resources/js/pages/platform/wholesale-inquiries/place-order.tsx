import { Head, Link, router } from '@inertiajs/react';
import { Building2, Package, UserRound } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import WholesaleInquiryController from '@/actions/App/Http/Controllers/Platform/WholesaleInquiryController';
import { PlaceOrderProductsSection } from '@/components/platform/wholesale/place-order-products-section';
import type {
    WholesalePlaceOrderBranch,
    WholesalePlaceOrderCustomerForm,
    WholesalePlaceOrderInquiry,
    WholesalePlaceOrderLine,
    WholesalePlaceOrderProduct,
} from '@/components/platform/wholesale/place-order-types';
import Heading from '@/components/heading';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

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
    products,
}: {
    inquiry: WholesalePlaceOrderInquiry;
    branch: WholesalePlaceOrderBranch | null;
    branchOptionsUrl: string;
    products: WholesalePlaceOrderProduct[];
}) {
    const [customer, setCustomer] = useState<WholesalePlaceOrderCustomerForm>({
        full_name: inquiry.full_name,
        phone: inquiry.phone,
        email: inquiry.email ?? '',
        company: inquiry.company ?? '',
        address: inquiry.address,
        note: inquiry.message,
    });
    const [lines, setLines] = useState<WholesalePlaceOrderLine[]>([]);

    useEffect(() => {
        setLines([]);
    }, [branch?.id]);

    const total = lines.reduce(
        (sum, line) => sum + line.price * line.quantity,
        0,
    );
    const itemCount = lines.reduce((sum, line) => sum + line.quantity, 0);

    const canPlace =
        branch !== null &&
        lines.length > 0 &&
        customer.full_name.trim() !== '' &&
        customer.phone.trim() !== '' &&
        customer.address.trim() !== '';

    const setCustomerField = (
        field: keyof WholesalePlaceOrderCustomerForm,
        value: string,
    ): void => {
        setCustomer((current) => ({ ...current, [field]: value }));
    };

    const selectBranch = (value: string | null): void => {
        router.get(
            WholesaleInquiryController.placeOrder.url(inquiry.id),
            value === null ? {} : { branch: value },
            {
                preserveState: false,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const addProduct = (product: WholesalePlaceOrderProduct): void => {
        setLines((current) => {
            if (current.some((line) => line.productId === product.id)) {
                return current;
            }

            return [
                ...current,
                {
                    key: product.id,
                    productId: product.id,
                    name: product.name,
                    sku: product.sku,
                    price: product.price,
                    stock: product.stock,
                    quantity: 1,
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
                    quantity: Math.max(1, Math.min(line.stock, quantity)),
                };
            }),
        );
    };

    const removeLine = (key: string): void => {
        setLines((current) => current.filter((line) => line.key !== key));
    };

    const handlePlaceOrder = (): void => {
        toast.message('Order placement is not wired yet', {
            description:
                'This screen is a UI shell. Live checkout comes next.',
        });
    };

    return (
        <>
            <Head title={`Place order · Inquiry #${inquiry.id}`} />

            <div className="flex w-full min-w-0 max-w-full flex-col gap-4 p-2 pb-28 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Place order"
                        description={`From wholesale inquiry #${inquiry.id} · ${inquiry.full_name}`}
                    />
                    <Button variant="secondary" asChild>
                        <Link
                            href={WholesaleInquiryController.show.url(
                                inquiry.id,
                            )}
                        >
                            Back
                        </Link>
                    </Button>
                </div>

                <SectionShell
                    icon={UserRound}
                    title="Customer"
                    description="Prefills from the inquiry — edit before placing the order."
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
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="wo-phone">Phone</Label>
                            <Input
                                id="wo-phone"
                                type="tel"
                                value={customer.phone}
                                onChange={(event) =>
                                    setCustomerField('phone', event.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="wo-email">Email</Label>
                            <Input
                                id="wo-email"
                                type="email"
                                value={customer.email}
                                onChange={(event) =>
                                    setCustomerField('email', event.target.value)
                                }
                            />
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
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="wo-address">Address</Label>
                            <Input
                                id="wo-address"
                                value={customer.address}
                                onChange={(event) =>
                                    setCustomerField(
                                        'address',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="wo-note">Note</Label>
                            <Textarea
                                id="wo-note"
                                rows={3}
                                value={customer.note}
                                onChange={(event) =>
                                    setCustomerField('note', event.target.value)
                                }
                            />
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
                                Available stock from the selected branch.
                            </p>
                        </div>
                    </div>
                    <PlaceOrderProductsSection
                        products={products}
                        lines={lines}
                        disabled={branch === null}
                        onAdd={addProduct}
                        onQuantityChange={updateLineQuantity}
                        onRemove={removeLine}
                    />
                </div>

                <div className="border-border/80 bg-background/95 sticky bottom-0 z-20 -mx-1 flex items-center justify-between gap-4 rounded-2xl border px-4 py-3 shadow-sm backdrop-blur-md supports-backdrop-filter:bg-background/85">
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
                        Place order
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
