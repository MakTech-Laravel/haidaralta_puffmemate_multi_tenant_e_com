import { usePage } from '@inertiajs/react';
import { Clock, Store } from 'lucide-react';
import { buildBranchNav } from '@/components/branch/nav';
import { Badge } from '@/components/ui/badge';
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
    const today = new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    }).format(new Date());

    return (
        <PortalShellLayout
            portal="branch"
            breadcrumbs={breadcrumbs}
            groupLabel={branch?.name ?? 'Floor'}
            logoHref={slug ? `/branch/${slug}/dashboard` : '/login'}
            nodes={slug ? buildBranchNav(slug) : []}
            sidebarVariant="inset"
            sidebarWidth="16.5rem"
            brandEyebrow="Branch"
            brandSubtitle={branch?.name ?? 'Store floor'}
            headerDensity="default"
            headerAccessory={
                <div className="hidden items-center gap-2 sm:flex">
                    <Badge variant="secondary" className="font-normal">
                        <Store className="size-3" />
                        Floor
                    </Badge>
                    <span className="text-muted-foreground text-xs">
                        {today}
                    </span>
                </div>
            }
            sidebarBanner={
                <div className="bg-card mx-2 mb-1 rounded-lg border p-3 group-data-[collapsible=icon]:hidden">
                    <div className="flex items-center gap-2 text-xs font-medium">
                        <Clock className="text-muted-foreground size-3.5" />
                        Store hours
                    </div>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Open · 08:00–21:00
                    </p>
                </div>
            }
        >
            {children}
        </PortalShellLayout>
    );
}
