import { usePage } from '@inertiajs/react';
import { buildPlatformNav } from '@/components/platform/nav';
import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import type { BreadcrumbItem } from '@/types';

export default function PlatformLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { catalog } = usePage().props;

    return (
        <PortalShellLayout
            portal="platform"
            breadcrumbs={breadcrumbs}
            logoHref="/platform/dashboard"
            sections={buildPlatformNav(catalog.show_bulk_import)}
            sidebarVariant="inset"
            sidebarWidth="16.5rem"
            brandEyebrow="Platform"
            headerDensity="compact"
        >
            {children}
        </PortalShellLayout>
    );
}
