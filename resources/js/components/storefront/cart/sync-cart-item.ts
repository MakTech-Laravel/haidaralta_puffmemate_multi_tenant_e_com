import { router } from '@inertiajs/react';
import { destroy, store, update } from '@/routes/cart';
import {
    formatStorefrontMoney,
    type StorefrontCart,
    type StorefrontCartItem,
} from '@/types/storefront';

const CART_PROPS = [
    'items',
    'itemCount',
    'subtotal',
    'formattedSubtotal',
    'storefront',
] as const;

type CartPageProps = {
    items?: StorefrontCartItem[];
    itemCount?: number;
    subtotal?: number;
    formattedSubtotal?: string;
    storefront?: {
        cart?: StorefrontCart;
    };
};

function withTotals(items: StorefrontCartItem[]): StorefrontCart {
    const itemCount = items.reduce((total, item) => total + item.quantity, 0);
    const subtotal = items.reduce((total, item) => total + item.lineTotal, 0);

    return {
        items,
        itemCount,
        subtotal,
        formattedSubtotal: formatStorefrontMoney(subtotal),
    };
}

function applyQuantity(
    cart: StorefrontCart,
    itemId: number,
    quantity: number,
): StorefrontCart {
    if (quantity < 1) {
        return withTotals(cart.items.filter((item) => item.id !== itemId));
    }

    return withTotals(
        cart.items.map((item) => {
            if (item.id !== itemId) {
                return item;
            }

            const lineTotal = item.unitPrice * quantity;

            return {
                ...item,
                quantity,
                lineTotal,
                formattedLineTotal: formatStorefrontMoney(lineTotal),
            };
        }),
    );
}

function optimisticCart(
    props: CartPageProps,
    itemId: number,
    quantity: number,
): Partial<CartPageProps> {
    const current = props.storefront?.cart ?? {
        items: props.items ?? [],
        itemCount: props.itemCount ?? 0,
        subtotal: props.subtotal ?? 0,
        formattedSubtotal: props.formattedSubtotal ?? formatStorefrontMoney(0),
    };
    const next = applyQuantity(current, itemId, quantity);

    return {
        items: next.items,
        itemCount: next.itemCount,
        subtotal: next.subtotal,
        formattedSubtotal: next.formattedSubtotal,
        storefront: {
            ...props.storefront,
            cart: next,
        },
    };
}

const visitOptions = {
    preserveScroll: true,
    preserveState: true,
    preserveUrl: true,
    async: true,
    replace: true,
    showProgress: false,
    only: [...CART_PROPS],
} as const;

export function addToCart(productSlug: string, quantity = 1, onFinish?: () => void): void {
    router.post(
        store.url(),
        { product: productSlug, quantity },
        {
            ...visitOptions,
            onSuccess: () => {
                window.dispatchEvent(new Event('storefront:cart-open'));
            },
            onFinish,
        },
    );
}

export function syncCartQuantity(itemId: number, quantity: number): void {
    router
        .optimistic((props: CartPageProps) =>
            optimisticCart(props, itemId, quantity),
        )
        .patch(update.url(itemId), { quantity }, visitOptions);
}

export function removeCartItem(itemId: number): void {
    router
        .optimistic((props: CartPageProps) => optimisticCart(props, itemId, 0))
        .delete(destroy.url(itemId), visitOptions);
}
