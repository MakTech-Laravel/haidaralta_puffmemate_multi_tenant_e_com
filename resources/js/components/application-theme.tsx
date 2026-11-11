import { useEffect } from 'react';

/** Keep in sync with --palette-green-900 in resources/css/app.css (browser-bar fallback only). */
const DEFAULT_THEME_COLOR = '#174B36';

/**
 * Keeps the browser address-bar colour (`meta[name="theme-color"]`) in sync with
 * Application Settings after Inertia visits (Blade paints the first load; this
 * covers SPA navigations / settings saves).
 *
 * Site colours are NOT set here. They live only in resources/css/app.css
 * (palette + semantic roles), so editing that file is enough to re-theme.
 *
 * Must receive `themeColor` as a prop — do not call `usePage()` here. This
 * component is mounted from `createInertiaApp({ withApp })` outside the Inertia
 * page tree.
 */
export function ApplicationTheme({
    themeColor = DEFAULT_THEME_COLOR,
}: {
    themeColor?: string | null;
}) {
    const color = themeColor || DEFAULT_THEME_COLOR;

    useEffect(() => {
        const meta = document.querySelector('meta[name="theme-color"]');

        if (meta) {
            meta.setAttribute('content', color);
        }
    }, [color]);

    return null;
}
