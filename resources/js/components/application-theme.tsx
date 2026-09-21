import { useEffect } from 'react';

const DEFAULT_THEME_COLOR = '#C4472F';

/**
 * Keeps CSS theme tokens in sync with Application Settings after Inertia visits
 * (Blade paints the first load; this covers SPA navigations / settings saves).
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
        const root = document.documentElement;
        root.style.setProperty('--primary', color);
        root.style.setProperty('--ring', color);
        root.style.setProperty('--sidebar-primary', color);
        root.style.setProperty('--sidebar-ring', color);
        root.style.setProperty('--chart-1', color);

        const meta = document.querySelector('meta[name="theme-color"]');

        if (meta) {
            meta.setAttribute('content', color);
        }
    }, [color]);

    return null;
}
