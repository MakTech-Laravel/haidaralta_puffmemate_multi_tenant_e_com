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
    const { branch } = usePage().props;
    const slug = branch?.slug ?? '';

    return (
        <PortalShellLayout
            breadcrumbs={breadcrumbs}
            groupLabel={branch?.name ?? 'Branch'}
            logoHref={slug ? `/branch/${slug}/dashboard` : '/login'}
            nodes={slug ? buildBranchNav(slug) : []}
        >
            {children}
        </PortalShellLayout>
    );
}
