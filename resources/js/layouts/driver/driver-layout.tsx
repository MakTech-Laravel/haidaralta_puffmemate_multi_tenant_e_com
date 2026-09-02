import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import { driverNav } from '@/components/driver/nav';
import type { BreadcrumbItem } from '@/types';

export default function DriverLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <PortalShellLayout
            breadcrumbs={breadcrumbs}
            groupLabel="Driver"
            logoHref="/driver/dashboard"
            nodes={driverNav}
        >
            {children}
        </PortalShellLayout>
    );
}
