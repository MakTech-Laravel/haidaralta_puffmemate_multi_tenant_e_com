import { usePage } from '@inertiajs/react';

const FALLBACK_LOGO = '/images/storefront/logo.png';

export type AppBrand = {
    name: string;
    shortName: string;
    description: string | null;
    logoUrl: string;
    hasCustomLogo: boolean;
    faviconUrl: string | null;
    themeColor: string;
    backgroundColor: string;
    timezone: string;
    dateFormat: string;
    timeFormat: string;
};

/**
 * Brand + format settings from Application Settings (shared Inertia props).
 */
export function useAppBrand(): AppBrand {
    const { name, application } = usePage().props;
    const customLogo = application?.logoUrl ?? null;

    return {
        name: name || application?.shortName || 'PuffMeMate',
        shortName: application?.shortName || name || 'PuffMeMate',
        description: application?.description ?? null,
        logoUrl: customLogo || FALLBACK_LOGO,
        hasCustomLogo: Boolean(customLogo),
        faviconUrl: application?.faviconUrl ?? null,
        themeColor: application?.themeColor || '#C4472F',
        backgroundColor: application?.backgroundColor || '#FFFFFF',
        timezone: application?.timezone || 'Asia/Dhaka',
        dateFormat: application?.dateFormat || 'Y-m-d',
        timeFormat: application?.timeFormat || 'H:i',
    };
}

export function resolveAppLogoUrl(logoUrl?: string | null): string {
    return logoUrl || FALLBACK_LOGO;
}
