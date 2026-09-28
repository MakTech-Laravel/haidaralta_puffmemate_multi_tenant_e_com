import { createInertiaApp, router } from '@inertiajs/react';
import { ErrorBoundary } from 'react-error-boundary';
import { AppToaster } from '@/components/app-toaster';
import { ApplicationTheme } from '@/components/application-theme';
import { NavigationOverlay } from '@/components/navigation-overlay';
import { ErrorBoundaryFallback } from '@/components/error-boundary/error-boundary-fallback';
import { pushError } from '@/components/error-boundary/error-store';
import { PwaInstallProvider } from '@/components/pwa/pwa-install-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import { getEcho, logEchoConfig } from '@/echo';
import { initializeTheme } from '@/hooks/use-appearance';
import { useDevErrorFallback } from '@/hooks/useDevErrorFallback';
import { syncViewerTimezoneCookie } from '@/lib/datetime';
import { startPwaServiceWorkerLifecycle } from '@/lib/pwa';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import BranchLayout from '@/layouts/branch/branch-layout';
import CustomerLayout from '@/layouts/customer/customer-layout';
import DriverLayout from '@/layouts/driver/driver-layout';
import PlatformLayout from '@/layouts/platform/platform-layout';
import SettingsPortalLayout from '@/layouts/settings/settings-portal-layout';
import StorefrontLayout from '@/layouts/storefront/storefront-layout';
import type { Auth } from '@/types/auth';

const fallbackAppName = import.meta.env.VITE_APP_NAME || '';
let resolvedAppName = fallbackAppName;

if (typeof window !== 'undefined') {
    syncViewerTimezoneCookie();
    getEcho();
    logEchoConfig();
    startPwaServiceWorkerLifecycle();
}

void createInertiaApp({
    title: (title) =>
        title ? `${title} - ${resolvedAppName}` : resolvedAppName,
    layout: (name) => {
        switch (true) {
            case name.startsWith('storefront/'):
                return StorefrontLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return SettingsPortalLayout;
            case name.startsWith('platform/'):
                return PlatformLayout;
            case name.startsWith('branch/'):
                return BranchLayout;
            case name.startsWith('driver/'):
                return DriverLayout;
            case name.startsWith('customer/'):
                return CustomerLayout;
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app, { page }) {
        const pageProps = page.props as {
            auth?: Auth;
            name?: string;
            application?: { name?: string | null; themeColor?: string | null };
        };
        const authUserId = pageProps.auth?.user?.id;
        const productName =
            pageProps.application?.name || pageProps.name || fallbackAppName;
        resolvedAppName = productName;

        return (
            <TooltipProvider delayDuration={0}>
                <PwaInstallProvider name={productName}>
                    <ErrorBoundary
                        FallbackComponent={ErrorBoundaryFallback}
                        onReset={() => router.reload()}
                        onError={(error, info) => {
                            pushError(error, info.componentStack ?? null);

                            if (useDevErrorFallback()) {
                                console.error(error, info.componentStack);
                            }
                        }}
                    >
                        <ApplicationTheme
                            themeColor={pageProps.application?.themeColor}
                        />
                        <NavigationOverlay />
                        {app}
                    </ErrorBoundary>
                    <AppToaster authUserId={authUserId} />
                </PwaInstallProvider>
            </TooltipProvider>
        );
    },
    progress: false,
});

// This will set light / dark mode on load...
initializeTheme();
