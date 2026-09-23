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
    const { catalog, shifts } = usePage().props;

    return (
        <PortalShellLayout
            portal="platform"
            breadcrumbs={breadcrumbs}
            logoHref="/platform/dashboard"
            sections={buildPlatformNav(
                catalog.show_bulk_import,
                catalog.show_branch_pricing,
                shifts.show_platform_administrator_shifts,
            )}
            sidebarVariant="inset"
            sidebarWidth="16.5rem"
            brandEyebrow="Platform"
            headerDensity="compact"
        >
            {children}
        </PortalShellLayout>
    );
}
