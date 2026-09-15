import { createInertiaApp, router } from '@inertiajs/react';
import { ErrorBoundary } from 'react-error-boundary';
import { AppToaster } from '@/components/app-toaster';
import { ErrorBoundaryFallback } from '@/components/error-boundary/error-boundary-fallback';
import { pushError } from '@/components/error-boundary/error-store';
import { TooltipProvider } from '@/components/ui/tooltip';
import { getEcho, logEchoConfig } from '@/echo';
import { initializeTheme } from '@/hooks/use-appearance';
import { useDevErrorFallback } from '@/hooks/useDevErrorFallback';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import BranchLayout from '@/layouts/branch/branch-layout';
import CustomerLayout from '@/layouts/customer/customer-layout';
import DriverLayout from '@/layouts/driver/driver-layout';
import PlatformLayout from '@/layouts/platform/platform-layout';
import SettingsPortalLayout from '@/layouts/settings/settings-portal-layout';
import StorefrontLayout from '@/layouts/storefront/storefront-layout';
import type { Auth } from '@/types/auth';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (typeof window !== 'undefined') {
    getEcho();
    logEchoConfig();
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
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
        const authUserId = (page.props as { auth?: Auth }).auth?.user?.id;

        return (
            <TooltipProvider delayDuration={0}>
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
                    {app}
                </ErrorBoundary>
                <AppToaster authUserId={authUserId} />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
