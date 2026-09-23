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
    compareAtPrice?: string | null;
    stock: number;
    category: string;
    image: string;
};

export type StorefrontFeature = {
    title: string;
    description: string;
};

export const storefrontCategoryItems: StorefrontCategoryItem[] = [
    {
        id: 'cigarettes',
        name: 'Cigarettes',
        description: 'Top brands & everyday packs',
        image: '/images/storefront/category-cigarettes.png',
        href: '/shop/cigarettes',
    },
    {
        id: 'vapes',
        name: 'Vapes',
        description: 'Devices, pods & disposables',
        image: '/images/storefront/category-vapes.png',
        href: '/shop/vapes',
    },
    {
        id: 'tobacco',
        name: 'Tobacco',
        description: 'Rolling & pipe tobacco',
        image: '/images/storefront/category-tobacco.png',
        href: '/shop/tobacco',
    },
    {
        id: 'nicotine-pouches',
        name: 'Nicotine Pouches',
        description: 'Smoke-free nicotine options',
        image: '/images/storefront/category-pouches.png',
        href: '/shop/nicotine-pouches',
        wide: true,
    },
    {
        id: 'accessories',
        name: 'Accessories',
        description: 'Lighters, cases & more',
        image: '/images/storefront/category-accessories.png',
        href: '/shop/accessories',
        wide: true,
    },
];

export const storefrontProducts: StorefrontProduct[] = [
    {
        id: '1',
        name: 'Marlboro Gold',
        description: 'Gold Pack • 20 Cigarettes',
        price: '$14.00',
        stock: 43,
        category: 'Cigarettes',
        image: '/images/storefront/product-marlboro-gold.jpg',
    },
    {
        id: '2',
        name: 'Winfield Blue',
        description: 'Blue Pack • 20 Cigarettes',
        price: '$13.50',
        stock: 28,
        category: 'Cigarettes',
        image: '/images/storefront/product-winfield-blue.jpg',
    },
    {
        id: '3',
        name: 'IQOS Iluma',
        description: 'Device Kit • Heat-not-burn',
        price: '$89.00',
        stock: 12,
        category: 'Vapes',
        image: '/images/storefront/product-marlboro-gold.jpg',
    },
    {
        id: '4',
        name: 'Vuse Go',
        description: 'Disposable • Mixed Berry',
        price: '$18.00',
        stock: 56,
        category: 'Vapes',
        image: '/images/storefront/product-winfield-blue.jpg',
    },
    {
        id: '5',
        name: 'Peter Stuyvesant Red',
        description: 'Red Pack • 20 Cigarettes',
        price: '$13.00',
        stock: 31,
        category: 'Cigarettes',
        image: '/images/storefront/b4343f447a376e4db4da6328e10eddefc632ddeb.jpg',
    },
    {
        id: '6',
        name: 'Horizon Menthol',
        description: 'Menthol Pack • 20 Cigarettes',
        price: '$12.50',
        stock: 19,
        category: 'Cigarettes',
        image: '/images/storefront/907b72737ecd36dbee5ac6c27fa0b02b906a44ee.jpg',
    },
    {
        id: '7',
        name: 'ZYN Cool Mint',
        description: 'Nicotine Pouches • 15 pcs',
        price: '$9.90',
        stock: 64,
        category: 'Nicotine Pouches',
        image: '/images/storefront/product-marlboro-gold.jpg',
    },
    {
        id: '8',
        name: 'Clipper Classic',
        description: 'Refillable Lighter',
        price: '$4.00',
        stock: 120,
        category: 'Accessories',
        image: '/images/storefront/product-winfield-blue.jpg',
    },
];

export const storefrontFeaturedProducts: StorefrontProduct[] =
    storefrontProducts.slice(0, 4);

export const storefrontNewArrivals: StorefrontProduct[] =
    storefrontProducts.slice(4, 8);

export const storefrontFeatures: StorefrontFeature[] = [
    {
        title: 'Same-Day Delivery',
        description: 'Available in selected local zones',
    },
    {
        title: 'Cash on Delivery',
        description: 'Pay when your order arrives',
    },
    {
        title: 'EFTPOS on Delivery',
        description: 'Driver carries an EFTPOS machine',
    },
    {
        title: 'Discreet Service',
        description: 'Private packaging and customer care',
    },
];

/** @deprecated Prefer storefrontCategoryItems */
export const storefrontCategories = storefrontCategoryItems.map(
    (category) => category.name,
);
