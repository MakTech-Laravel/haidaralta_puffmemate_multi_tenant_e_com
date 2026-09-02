import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import { customerNav } from '@/components/customer/nav';
import type { BreadcrumbItem } from '@/types';

export default function CustomerLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <PortalShellLayout
            breadcrumbs={breadcrumbs}
            groupLabel="Customer"
            logoHref="/dashboard"
            nodes={customerNav}
        >
            {children}
        </PortalShellLayout>
    );
}
