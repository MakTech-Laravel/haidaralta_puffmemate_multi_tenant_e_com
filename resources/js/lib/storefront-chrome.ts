export const storefrontAnnouncement =
    'INSTANT DELIVERY · CASH ON DELIVERY · EFTPOS AVAILABLE · FREE SHIPPING & HANDLING';

/** Upload the brand mark to this path (`.png` or swap to `.svg` if preferred). */
export const storefrontLogoSrc = '/images/storefront/logo.png';

export type StorefrontChromeItem = {
    label: string;
    /** Named route when it exists; otherwise links fall back to shop. */
    route?: 'home' | 'shop' | 'about' | 'contact';
};

export const storefrontNavItems: StorefrontChromeItem[] = [
    { label: 'Home', route: 'home' },
    { label: 'Cigarettes' },
    { label: 'Tobacco' },
    { label: 'Vapes' },
    { label: 'Nicotine Pouches' },
    { label: 'Packs & Saves' },
    { label: 'Wholesale' },
];

export const storefrontFooterShop: StorefrontChromeItem[] = [
    { label: 'Cigarettes' },
    { label: 'Vapes' },
    { label: 'Tobacco' },
    { label: 'Nicotine' },
];

export const storefrontFooterCustomer: StorefrontChromeItem[] = [
    { label: 'Delivery' },
    { label: 'Contact', route: 'contact' },
    { label: 'WhatsApp' },
    { label: 'FAQs' },
];

export const storefrontFooterBrand: StorefrontChromeItem[] = [
    { label: 'About Us', route: 'about' },
    { label: 'Wholesale' },
    { label: 'Price Beat' },
    { label: 'Privacy' },
];

export const storefrontFooterBlurb =
    'Australia-wide online tobacconist. Premium products, competitive pricing and a simple delivery experience.';
