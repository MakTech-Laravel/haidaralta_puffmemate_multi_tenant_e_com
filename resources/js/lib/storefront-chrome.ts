export const storefrontAnnouncement =
    'INSTANT DELIVERY · CASH ON DELIVERY · EFTPOS AVAILABLE · FREE SHIPPING & HANDLING';

/** Upload the brand mark to this path (`.png` or swap to `.svg` if preferred). */
export const storefrontLogoSrc = '/images/storefront/logo.png';

export type StorefrontChromeItem = {
    label: string;
    /** Named route when it exists; otherwise links fall back to shop. */
    route?: 'home' | 'shop' | 'about' | 'contact';
    /** Category slug for /shop/{category} links. */
    categorySlug?: string;
};

export const storefrontNavItems: StorefrontChromeItem[] = [
    { label: 'Home', route: 'home' },
    { label: 'Cigarettes', categorySlug: 'cigarettes' },
    { label: 'Tobacco', categorySlug: 'tobacco' },
    { label: 'Vapes', categorySlug: 'vapes' },
    { label: 'Nicotine Pouches', categorySlug: 'nicotine-pouches' },
    { label: 'Packs & Saves', categorySlug: 'packs-saves' },
    { label: 'Wholesale', categorySlug: 'wholesale' },
];

export const storefrontFooterShop: StorefrontChromeItem[] = [
    { label: 'Cigarettes', categorySlug: 'cigarettes' },
    { label: 'Vapes', categorySlug: 'vapes' },
    { label: 'Tobacco', categorySlug: 'tobacco' },
    { label: 'Nicotine', categorySlug: 'nicotine' },
];

export const storefrontFooterCustomer: StorefrontChromeItem[] = [
    { label: 'Delivery' },
    { label: 'Contact', route: 'contact' },
    { label: 'WhatsApp' },
    { label: 'FAQs' },
];

export const storefrontFooterBrand: StorefrontChromeItem[] = [
    { label: 'About Us', route: 'about' },
    { label: 'Wholesale', categorySlug: 'wholesale' },
    { label: 'Privacy' },
];

export const storefrontFooterBlurb =
    'Australia-wide online tobacconist. Premium products, competitive pricing and a simple delivery experience.';
