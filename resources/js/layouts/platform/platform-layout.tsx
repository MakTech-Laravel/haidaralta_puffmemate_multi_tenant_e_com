import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import { platformNav } from '@/components/platform/nav';
import type { BreadcrumbItem } from '@/types';

export default function PlatformLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <PortalShellLayout
            breadcrumbs={breadcrumbs}
            groupLabel="Platform"
            logoHref="/platform/dashboard"
            nodes={platformNav}
        >
            {children}
        </PortalShellLayout>
    );
}
