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
};

export type StorefrontFeature = {
    title: string;
    description: string;
};
