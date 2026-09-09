import type { ReactNode } from 'react';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import { StorefrontHeader } from '@/components/storefront/storefront-header';

export default function StorefrontLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="bg-background text-foreground flex min-h-svh flex-col">
            <StorefrontHeader />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
        </div>
    );
}
