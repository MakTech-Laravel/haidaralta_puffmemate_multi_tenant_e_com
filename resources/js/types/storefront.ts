export type StorefrontCategoryItem = {
    id: string;
    name: string;
    description: string;
    image: string;
    href: string;
    wide?: boolean;
};

export type StorefrontHeroSlide = {
    id: string;
    eyebrow: string;
    title: string;
    description: string;
    image: string;
    primaryCta: { label: string; href: string };
    secondaryCta: { label: string; href: string };
};

export type StorefrontProduct = {
    id: string;
    name: string;
    description: string;
    price: string;
    compareAtPrice?: string | null;
    category: string;
    image: string;
    views: string;
};

export type StorefrontPack = {
    id: string;
    name: string;
    eyebrow: string;
    description: string;
    image: string;
    price: string;
    currency?: string;
    savings?: string | null;
    stockLeft?: number | null;
};

export type StorefrontFeaturedPack = StorefrontPack & {
    regularPrice: string;
    discountPercent: number;
    branchName: string;
    items: { name: string; quantity: number }[];
};

export type StorefrontFeature = {
    title: string;
    description: string;
};

export type StorefrontCartItem = {
    id: number;
    productId: number;
    slug: string;
    name: string;
    category: string;
    description: string;
    image: string;
    unitPrice: number;
    formattedUnitPrice: string;
    quantity: number;
    lineTotal: number;
    formattedLineTotal: string;
};

export type StorefrontCart = {
    itemCount: number;
    items: StorefrontCartItem[];
    subtotal: number;
    formattedSubtotal: string;
};

export type StorefrontAddress = {
    id: number;
    type: string;
    receiver_name: string;
    phone: string;
    address: string;
    city: string | null;
    postal_code: string | null;
    latitude: string | null;
    longitude: string | null;
    is_default: boolean;
};

export type StorefrontDeliveryMethod = {
    id: string;
    label: string;
    description: string;
    requiresSlot: boolean;
};

export type StorefrontTimeSlot = {
    id: string;
    label: string;
};

export type StorefrontPaymentMethod = {
    id: string;
    label: string;
    title?: string;
    description: string;
    badge?: string;
};

export function formatStorefrontMoney(amount: number): string {
    return `$${amount.toFixed(2)}`;
}
