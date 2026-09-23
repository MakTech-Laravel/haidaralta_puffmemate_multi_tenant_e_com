import { usePage } from '@inertiajs/react';
import { buildBranchNav } from '@/components/branch/nav';
import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import type { BreadcrumbItem } from '@/types';

export default function BranchLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { branch, catalog } = usePage().props;
    const slug = branch?.slug ?? '';

    return (
        <PortalShellLayout
            portal="branch"
            breadcrumbs={breadcrumbs}
            logoHref={slug ? `/branch/${slug}/dashboard` : '/login'}
            sections={
                slug
                    ? buildBranchNav(
                          slug,
                          catalog.branch_can_view_categories,
                          catalog.show_bulk_import,
                          catalog.show_branch_pricing,
                      )
                    : []
            }
            sidebarVariant="inset"
            sidebarWidth="16.5rem"
            brandEyebrow="Branch"
            brandSubtitle={branch?.name}
            headerDensity="compact"
        >
            {children}
        </PortalShellLayout>
    );
}
