import { Activity, Gauge, Radio } from 'lucide-react';
import { platformNav } from '@/components/platform/nav';
import { Badge } from '@/components/ui/badge';
import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
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
            portal="platform"
            breadcrumbs={breadcrumbs}
            groupLabel="Command"
            groupLabelClassName="text-[10px] tracking-[0.18em]"
            logoHref="/platform/dashboard"
            nodes={platformNav}
            sidebarVariant="sidebar"
            sidebarWidth="15rem"
            sidebarClassName="border-l-4 border-l-primary"
            sidebarHeaderClassName="bg-primary text-primary-foreground"
            brandEyebrow="Platform"
            brandSubtitle="Ops console"
            brandTone="inverse"
            navClassNames={{
                row: 'h-7 text-xs',
                icon: 'size-3.5',
            }}
            headerDensity="compact"
            headerAccent="primary-bar"
            headerAccessory={
                <div className="hidden items-center gap-1.5 sm:flex">
                    <Badge variant="secondary" className="font-normal">
                        <Radio className="size-3" />
                        Queues
                    </Badge>
                    <Badge variant="outline" className="font-normal">
                        <Gauge className="size-3" />
                        Pulse
                    </Badge>
                    <Badge variant="outline" className="font-normal">
                        <Activity className="size-3" />
                        Horizon
                    </Badge>
                </div>
            }
            sidebarFooterExtra={
                <p className="text-muted-foreground px-2 text-[10px] tracking-[0.16em] uppercase group-data-[collapsible=icon]:hidden">
                    Command center · demo
                </p>
            }
        >
            {children}
        </PortalShellLayout>
    );
}
