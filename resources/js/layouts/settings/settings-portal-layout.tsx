import { usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import BranchLayout from '@/layouts/branch/branch-layout';
import PlatformLayout from '@/layouts/platform/platform-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

export default function SettingsPortalLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { portal } = usePage().props;
    const inner = <SettingsLayout>{children}</SettingsLayout>;

    if (portal === 'platform') {
        return (
            <PlatformLayout breadcrumbs={breadcrumbs}>{inner}</PlatformLayout>
        );
    }

    if (portal === 'branch') {
        return <BranchLayout breadcrumbs={breadcrumbs}>{inner}</BranchLayout>;
    }

    return <AppLayout breadcrumbs={breadcrumbs}>{inner}</AppLayout>;
}
