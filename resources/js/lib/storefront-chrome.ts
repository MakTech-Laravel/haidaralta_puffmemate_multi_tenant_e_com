export const storefrontAnnouncement =
    'INSTANT DELIVERY · CASH ON DELIVERY · EFTPOS AVAILABLE · FREE SHIPPING & HANDLING';

/** Upload the brand mark to this path (`.png` or swap to `.svg` if preferred). */
export const storefrontLogoSrc = '/images/storefront/logo.png';

export type StorefrontChromeItem = {
    label: string;
    /** Named route when it exists; otherwise links fall back to shop. */
    route?:
        | 'home'
        | 'shop'
        | 'about'
        | 'faqs'
        | 'contact'
        | 'wholesale'
        | 'packs-saves'
        | 'privacy'
        | 'terms';
    /** Category slug for /shop/{category} links. */
    categorySlug?: string;
};

export type StorefrontNavCategory = {
    label: string;
    categorySlug: string;
};

/** Fixed header slots — dynamic categories are inserted between Shop and Packs & Saves. */
export const storefrontFixedNavLead: StorefrontChromeItem[] = [
    { label: 'Home', route: 'home' },
    { label: 'Shop', route: 'shop' },
];

export const storefrontFixedNavTrail: StorefrontChromeItem[] = [
    { label: 'Packs & Saves', route: 'packs-saves' },
    { label: 'Wholesale', route: 'wholesale' },
];

/**
 * Build header nav: Home · Shop · [nav categories by nav_sort] · Packs & Saves · Wholesale.
 */
export function buildStorefrontNavItems(
    navCategories: StorefrontNavCategory[] = [],
): StorefrontChromeItem[] {
    return [
        ...storefrontFixedNavLead,
        ...navCategories.map((category) => ({
            label: category.label,
            categorySlug: category.categorySlug,
        })),
        ...storefrontFixedNavTrail,
    ];
}

/** @deprecated Prefer buildStorefrontNavItems(storefront.navCategories). */
export const storefrontNavItems: StorefrontChromeItem[] =
    buildStorefrontNavItems([]);

export const storefrontFooterShop: StorefrontChromeItem[] = [
    { label: 'Cigarettes', categorySlug: 'cigarettes' },
    { label: 'Vapes', categorySlug: 'vapes' },
    { label: 'Tobacco', categorySlug: 'tobacco' },
    { label: 'Nicotine Pouches', categorySlug: 'nicotine-pouches' },
    { label: 'Accessories', categorySlug: 'accessories' },
    { label: 'Packs & Saves', route: 'packs-saves' },
];

export const storefrontFooterCustomer: StorefrontChromeItem[] = [
    { label: 'Contact', route: 'contact' },
    { label: 'WhatsApp' },
    { label: 'FAQs', route: 'faqs' },
];

export const storefrontFooterBrand: StorefrontChromeItem[] = [
    { label: 'About Us', route: 'about' },
    { label: 'Wholesale', route: 'wholesale' },
    { label: 'Privacy', route: 'privacy' },
    { label: 'Terms', route: 'terms' },
];

export const storefrontFooterBlurb =
    'Australia-wide online tobacconist. Premium products, competitive pricing and a simple delivery experience.';
