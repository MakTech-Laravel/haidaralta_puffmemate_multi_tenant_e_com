import type { ReactNode } from 'react';
import { AgeGateDialog } from '@/components/storefront/age-gate-dialog';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';

export default function StorefrontLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="bg-background text-foreground flex min-h-svh max-w-full flex-col overflow-x-clip">
            <StorefrontHeader />
            <main className="min-w-0 flex-1">{children}</main>
            <StorefrontFooter />
            <AgeGateDialog />
        </div>
    );
}
