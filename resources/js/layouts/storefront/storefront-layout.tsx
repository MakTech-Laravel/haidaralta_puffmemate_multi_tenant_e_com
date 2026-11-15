import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AgeGateDialog } from '@/components/storefront/age-gate-dialog';
import {
    isStorefrontCheckout,
    StorefrontMobileNav,
    storefrontMobileNavPadding,
} from '@/components/storefront/storefront-mobile-nav';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';
import { cn } from '@/lib/utils';

export default function StorefrontLayout({
    children,
}: {
    children: ReactNode;
}) {
    const checkout = isStorefrontCheckout(usePage().url);

    return (
        <div
            className={cn(
                'bg-background text-foreground flex min-h-svh max-w-full flex-col overflow-x-clip',
                checkout ? undefined : storefrontMobileNavPadding,
            )}
        >
            <StorefrontHeader />
            <main className="flex min-w-0 flex-1 flex-col">{children}</main>
            <StorefrontFooter />
            <AgeGateDialog />
            {checkout ? null : <StorefrontMobileNav />}
        </div>
    );
}
