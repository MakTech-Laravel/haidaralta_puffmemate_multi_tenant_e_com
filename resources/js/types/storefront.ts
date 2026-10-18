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
    slug: string;
    branchSlug: string;
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

export type StorefrontPackDetail = Omit<StorefrontFeaturedPack, 'items'> & {
    items: { name: string; quantity: number; image: string }[];
    images: string[];
    productCount: number;
    totalUnits: number;
    startsAt: string | null;
    endsAt: string | null;
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
    compareAtUnitPrice: number | null;
    formattedCompareAtUnitPrice: string | null;
    quantity: number;
    maxQuantity: number;
    lineTotal: number;
    formattedLineTotal: string;
};

export type StorefrontCart = {
    itemCount: number;
    items: StorefrontCartItem[];
    subtotal: number;
    formattedSubtotal: string;
};

export type StorefrontSavedAddress = {
    name: string;
    mobile: string;
    address: string;
    city: string | null;
    postal_code: string | null;
    latitude: string | null;
    longitude: string | null;
};

export type StorefrontTimeSlot = {
    id: string;
    label: string;
};

export type StorefrontDeliveryMethod = {
    id: string;
    label: string;
    description: string;
    requiresSlot: boolean;
    timeSlots: StorefrontTimeSlot[];
};

export type StorefrontPaymentMethod = {
    id: string;
    label: string;
    title?: string;
    description: string;
    badge?: string;
};

export function formatStorefrontMoney(amount: number): string {
    return `$${amount.toLocaleString('en-AU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

export function formatStorefrontAud(amount: string): string {
    return `A$${amount}`;
}
