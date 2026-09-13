export type StorefrontHeroSlide = {
    id: string;
    eyebrow: string;
    title: string;
    description: string;
    image: string;
    primaryCta: { label: string; href: string };
    secondaryCta: { label: string; href: string };
};

export type StorefrontCategoryItem = {
    id: string;
    name: string;
    description: string;
    image: string;
    href: string;
    wide?: boolean;
};

export type StorefrontProduct = {
    id: string;
    name: string;
    description: string;
    price: string;
    stock: number;
    category: string;
    image: string;
};

export type StorefrontFeature = {
    title: string;
    description: string;
};

export const storefrontHeroSlides: StorefrontHeroSlide[] = [
    {
        id: '1',
        eyebrow: 'PUFFMEMATE AUSTRALIA',
        title: 'Your Life. Your Choice.',
        description:
            'A cleaner, premium shopping experience for cigarettes, vapes, tobacco, nicotine products and accessories.',
        image: '/images/storefront/hero-1.jpg',
        primaryCta: { label: 'Shop Products', href: '/shop' },
        secondaryCta: { label: 'Explore Deals', href: '/shop' },
    },
    {
        id: '2',
        eyebrow: 'PUFFMEMATE AUSTRALIA',
        title: 'Premium picks. Local stock.',
        description:
            'Browse featured packs and new arrivals from nearby branches — ready when you are.',
        image: '/images/storefront/hero-2.jpg',
        primaryCta: { label: 'Shop Products', href: '/shop' },
        secondaryCta: { label: 'Explore Deals', href: '/shop' },
    },
];

export const storefrontCategoryItems: StorefrontCategoryItem[] = [
    {
        id: 'cigarettes',
        name: 'Cigarettes',
        description: 'Top brands & everyday packs',
        image: '/images/storefront/category-cigarettes.jpg',
        href: '/shop',
    },
    {
        id: 'vapes',
        name: 'Vapes',
        description: 'Devices, pods & disposables',
        image: '/images/storefront/category-vapes.jpg',
        href: '/shop',
    },
    {
        id: 'tobacco',
        name: 'Tobacco',
        description: 'Rolling & pipe tobacco',
        image: '/images/storefront/category-tobacco.jpg',
        href: '/shop',
    },
    {
        id: 'nicotine-pouches',
        name: 'Nicotine Pouches',
        description: 'Smoke-free nicotine options',
        image: '/images/storefront/category-pouches.jpg',
        href: '/shop',
        wide: true,
    },
    {
        id: 'accessories',
        name: 'Accessories',
        description: 'Lighters, cases & more',
        image: '/images/storefront/category-accessories.jpg',
        href: '/shop',
        wide: true,
    },
];

export const storefrontProducts: StorefrontProduct[] = [
    {
        id: '1',
        name: 'Marlboro Gold',
        description: 'Gold Pack • 20 Cigarettes',
        price: 'Start from $14.00',
        stock: 43,
        category: 'Cigarettes',
        image: '/images/storefront/product-marlboro-gold.jpg',
    },
    {
        id: '2',
        name: 'Winfield Blue',
        description: 'Blue Pack • 20 Cigarettes',
        price: 'Start from $13.50',
        stock: 28,
        category: 'Cigarettes',
        image: '/images/storefront/product-winfield-blue.jpg',
    },
    {
        id: '3',
        name: 'IQOS Iluma',
        description: 'Device Kit • Heat-not-burn',
        price: 'Start from $89.00',
        stock: 12,
        category: 'Vapes',
        image: '/images/storefront/product-iqos.jpg',
    },
    {
        id: '4',
        name: 'Vuse Go',
        description: 'Disposable • Mixed Berry',
        price: 'Start from $18.00',
        stock: 56,
        category: 'Vapes',
        image: '/images/storefront/product-vuse.jpg',
    },
    {
        id: '5',
        name: 'Peter Stuyvesant Red',
        description: 'Red Pack • 20 Cigarettes',
        price: 'Start from $13.00',
        stock: 31,
        category: 'Cigarettes',
        image: '/images/storefront/product-stuyvesant.jpg',
    },
    {
        id: '6',
        name: 'Horizon Menthol',
        description: 'Menthol Pack • 20 Cigarettes',
        price: 'Start from $12.50',
        stock: 19,
        category: 'Cigarettes',
        image: '/images/storefront/product-horizon.jpg',
    },
    {
        id: '7',
        name: 'ZYN Cool Mint',
        description: 'Nicotine Pouches • 15 pcs',
        price: 'Start from $9.90',
        stock: 64,
        category: 'Nicotine Pouches',
        image: '/images/storefront/product-zyn.jpg',
    },
    {
        id: '8',
        name: 'Clipper Classic',
        description: 'Refillable Lighter',
        price: 'Start from $4.00',
        stock: 120,
        category: 'Accessories',
        image: '/images/storefront/product-clipper.jpg',
    },
];

export const storefrontFeaturedProducts: StorefrontProduct[] =
    storefrontProducts.slice(0, 4);

export const storefrontNewArrivals: StorefrontProduct[] =
    storefrontProducts.slice(4, 8);

export const storefrontFeatures: StorefrontFeature[] = [
    {
        title: 'Same-Day Delivery',
        description: 'Order before cut-off for local same-day drop-off.',
    },
    {
        title: 'Cash on Delivery',
        description: 'Pay in cash when your order arrives.',
    },
    {
        title: 'EFTPOS on Delivery',
        description: 'Card payments accepted at the door.',
    },
    {
        title: 'Discreet Service',
        description: 'Plain packaging and private delivery handling.',
    },
];

/** @deprecated Prefer storefrontCategoryItems */
export const storefrontCategories = storefrontCategoryItems.map(
    (category) => category.name,
);
