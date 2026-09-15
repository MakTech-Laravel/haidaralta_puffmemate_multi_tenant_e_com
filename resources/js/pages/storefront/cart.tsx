import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useMemo, useState } from 'react';
import { CartItems } from '@/components/storefront/cart/cart-items';
import { CartOrderSummary } from '@/components/storefront/cart/cart-order-summary';
import type {
    CartBranchOption,
    CartGroup,
    CartSummary,
} from '@/lib/storefront-cart';
import { category as shopCategory } from '@/routes/shop';

type CartPageProps = {
    itemCount: number;
    stockReserved: boolean;
    groups: CartGroup[];
    summary: CartSummary;
    branches: CartBranchOption[];
};

const DELIVERY_FEE = 3.5;
const FIRST_DELIVERY_DISCOUNT = -3.5;

function buildSummary(groups: CartGroup[]): CartSummary {
    const branches = groups.flatMap((group) => {
        const subtotal = group.items.reduce((itemTotal, item) => {
            if (!item.selected || item.unavailable) {
                return itemTotal;
            }

            return itemTotal + item.unitPrice * item.quantity;
        }, 0);

        if (subtotal <= 0) {
            return [];
        }

        return [
            {
                branchName: group.branchName,
                lines: [
                    { label: 'Subtotal', amount: subtotal, tone: 'default' },
                    {
                        label: 'Delivery fee',
                        amount: DELIVERY_FEE,
                        tone: 'default',
                    },
                    {
                        label: 'First delivery discount',
                        amount: FIRST_DELIVERY_DISCOUNT,
                        tone: 'discount',
                    },
                ],
            },
        ];
    });

    const total = branches.reduce((sum, branch) => {
        return (
            sum +
            branch.lines.reduce((lineSum, line) => lineSum + line.amount, 0)
        );
    }, 0);

    return { branches, total };
}

export default function StorefrontCart({
    itemCount: _itemCount,
    stockReserved,
    groups: initialGroups,
    branches,
}: CartPageProps) {
    const [groups, setGroups] = useState(initialGroups);

    const summary = useMemo(() => buildSummary(groups), [groups]);
    const displayCount = groups.reduce(
        (count, group) => count + group.items.length,
        0,
    );

    const updateGroups = (updater: (current: CartGroup[]) => CartGroup[]) => {
        setGroups((current) => updater(current));
    };

    return (
        <>
            <Head title="Cart" />

            <div className="bg-muted/50 flex-1">
                <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                    <Link
                        href={shopCategory.url('cigarettes')}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm font-semibold transition"
                    >
                        <ArrowLeft className="size-4" />
                        Continue shopping
                    </Link>

                    <div className="mt-4 mb-8 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div className="flex flex-wrap items-baseline gap-3">
                                <h1 className="text-foreground text-4xl font-extrabold tracking-tight">
                                    Your cart
                                </h1>
                                <span className="text-muted-foreground text-lg font-medium">
                                    {displayCount}{' '}
                                    {displayCount === 1 ? 'item' : 'items'}
                                </span>
                            </div>
                            <p className="text-muted-foreground mt-2 text-sm font-medium">
                                Every item keeps its selected product branch
                                through checkout.
                            </p>
                        </div>

                        {stockReserved ? (
                            <span className="text-tertiary bg-background inline-flex items-center gap-1.5 rounded-full border border-current/20 px-3 py-1.5 text-xs font-semibold">
                                <span
                                    className="bg-tertiary size-1.5 rounded-full"
                                    aria-hidden
                                />
                                Stock reserved
                            </span>
                        ) : null}
                    </div>

                    <div className="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                        <CartItems
                            className="min-w-0"
                            groups={groups}
                            branches={branches}
                            onToggleGroup={(groupId, selected) => {
                                updateGroups((current) =>
                                    current.map((group) =>
                                        group.id === groupId
                                            ? {
                                                  ...group,
                                                  selected,
                                                  items: group.items.map(
                                                      (item) => ({
                                                          ...item,
                                                          selected,
                                                      }),
                                                  ),
                                              }
                                            : group,
                                    ),
                                );
                            }}
                            onToggleItem={(groupId, itemId, selected) => {
                                updateGroups((current) =>
                                    current.map((group) => {
                                        if (group.id !== groupId) {
                                            return group;
                                        }

                                        const items = group.items.map((item) =>
                                            item.id === itemId
                                                ? { ...item, selected }
                                                : item,
                                        );

                                        return {
                                            ...group,
                                            items,
                                            selected: items.every(
                                                (item) => item.selected,
                                            ),
                                        };
                                    }),
                                );
                            }}
                            onQuantityChange={(groupId, itemId, quantity) => {
                                updateGroups((current) =>
                                    current.map((group) =>
                                        group.id === groupId
                                            ? {
                                                  ...group,
                                                  items: group.items.map(
                                                      (item) =>
                                                          item.id === itemId
                                                              ? {
                                                                    ...item,
                                                                    quantity,
                                                                }
                                                              : item,
                                                  ),
                                              }
                                            : group,
                                    ),
                                );
                            }}
                            onBranchChange={(groupId, itemId, branchId) => {
                                const branch = branches.find(
                                    (option) => option.id === branchId,
                                );

                                updateGroups((current) =>
                                    current.map((group) =>
                                        group.id === groupId
                                            ? {
                                                  ...group,
                                                  items: group.items.map(
                                                      (item) =>
                                                          item.id === itemId
                                                              ? {
                                                                    ...item,
                                                                    branchId,
                                                                    branchName:
                                                                        branch?.name ??
                                                                        item.branchName,
                                                                }
                                                              : item,
                                                  ),
                                              }
                                            : group,
                                    ),
                                );
                            }}
                            onRemoveItem={(groupId, itemId) => {
                                updateGroups((current) =>
                                    current
                                        .map((group) =>
                                            group.id === groupId
                                                ? {
                                                      ...group,
                                                      items: group.items.filter(
                                                          (item) =>
                                                              item.id !==
                                                              itemId,
                                                      ),
                                                  }
                                                : group,
                                        )
                                        .filter(
                                            (group) => group.items.length > 0,
                                        ),
                                );
                            }}
                        />

                        <CartOrderSummary className="min-w-0" summary={summary} />
                    </div>
                </div>
            </div>
        </>
    );
}
