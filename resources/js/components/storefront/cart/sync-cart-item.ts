import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { destroy, store, update } from '@/routes/cart';
import {
    formatStorefrontMoney,
    type StorefrontCart,
    type StorefrontCartItem,
} from '@/types/storefront';

export const CART_OPEN_EVENT = 'storefront:cart-open';

let addedToastSeq = 0;

export function openCartSheet(): void {
    window.dispatchEvent(new Event(CART_OPEN_EVENT));
}

function notifyAddedToCart(): void {
    addedToastSeq += 1;

    toast.success('Product added to cart', {
        id: `cart-added-${addedToastSeq}`,
        action: {
            label: 'View Cart',
            onClick: () => openCartSheet(),
        },
    });
}

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
    only: [...CART_PROPS] as string[],
} as const;

type AddToCartSuccessPage = {
    flash?: {
        view_counted?: boolean;
    };
};

type AddToCartOptions = {
    countView?: boolean;
    onSuccess?: (page: AddToCartSuccessPage) => void;
};

export function addToCart(
    productSlug: string,
    quantity = 1,
    onFinish?: () => void,
    options: AddToCartOptions = {},
): void {
    router.post(
        store.url(),
        {
            product: productSlug,
            quantity,
            ...(options.countView ? { count_view: true } : {}),
        },
        {
            ...visitOptions,
            onSuccess: (page) => {
                notifyAddedToCart();
                options.onSuccess?.(page as AddToCartSuccessPage);
            },
            onFinish,
        },
    );
}

export function syncCartQuantity(
    item: Pick<StorefrontCartItem, 'id' | 'maxQuantity'>,
    quantity: number,
): void {
    if (quantity > item.maxQuantity) {
        toast.info(`You can add up to ${item.maxQuantity} of this item.`, {
            id: `cart-max-${item.id}`,
        });

        return;
    }

    router
        .optimistic((props: CartPageProps) =>
            optimisticCart(props, item.id, quantity),
        )
        .patch(
            update.url(item.id),
            { quantity },
            {
                ...visitOptions,
                onError: (errors) => {
                    toast.error(
                        errors.quantity ?? 'We could not update your cart.',
                        { id: `cart-error-${item.id}` },
                    );
                },
            },
        );
}

export function removeCartItem(itemId: number): void {
    router
        .optimistic((props: CartPageProps) => optimisticCart(props, itemId, 0))
        .delete(destroy.url(itemId), visitOptions);
}
