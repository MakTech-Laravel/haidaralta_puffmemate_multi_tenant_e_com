import type { ReactNode } from 'react';
import { AgeGateDialog } from '@/components/storefront/age-gate-dialog';
import { usePage } from '@inertiajs/react';
import {
    isStorefrontCheckout,
    StorefrontMobileNav,
    storefrontMobileNavPadding,
} from '@/components/storefront/storefront-mobile-nav';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';

export default function StorefrontLayout({
    children,
}: {
    children: ReactNode;
}) {
    const checkout = isStorefrontCheckout(usePage().url);

    return (
        <div className="bg-background text-foreground flex min-h-svh max-w-full flex-col overflow-x-clip">
            <StorefrontHeader />
            <main className="flex min-w-0 flex-1 flex-col">{children}</main>
            <StorefrontFooter
                className={
                    checkout
                        ? 'pb-[calc(8.5rem+env(safe-area-inset-bottom))] lg:pb-0'
                        : storefrontMobileNavPadding
                }
            />
            <AgeGateDialog />
            <StorefrontMobileNav />
        </div>
    );
}
