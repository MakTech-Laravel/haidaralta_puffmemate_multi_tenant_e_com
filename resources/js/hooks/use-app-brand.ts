import { usePage } from '@inertiajs/react';
import { resolveViewerTimezone } from '@/lib/datetime';

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
    /** Viewer browser timezone (not an application setting). */
    timezone: string;
    dateFormat: string;
    timeFormat: string;
};

/**
 * Brand + format settings from Application Settings (shared Inertia props).
 * Timezone comes from the viewer's browser, not admin settings.
 */
export function useAppBrand(): AppBrand {
    const { name, application } = usePage().props;
    const customLogo = application?.logoUrl ?? null;
    const envName = import.meta.env.VITE_APP_NAME;
    const displayName =
        application?.name || name || application?.shortName || envName;

    return {
        name: displayName,
        shortName: application?.shortName || displayName,
        description: application?.description ?? null,
        logoUrl: customLogo || FALLBACK_LOGO,
        hasCustomLogo: Boolean(customLogo),
        faviconUrl: application?.faviconUrl ?? null,
        themeColor: application?.themeColor || '#C4472F',
        backgroundColor: application?.backgroundColor || '#FFFFFF',
        timezone: resolveViewerTimezone(),
        dateFormat: application?.dateFormat || 'Y-m-d',
        timeFormat: application?.timeFormat || 'H:i',
    };
}

export function resolveAppLogoUrl(logoUrl?: string | null): string {
    return logoUrl || FALLBACK_LOGO;
}
