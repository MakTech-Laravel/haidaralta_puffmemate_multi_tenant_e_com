import { Bike, CircleDot } from 'lucide-react';
import { driverNav } from '@/components/driver/nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
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
            portal="driver"
            breadcrumbs={breadcrumbs}
            groupLabel="Route"
            groupLabelClassName="text-xs font-semibold"
            logoHref="/driver/dashboard"
            nodes={driverNav}
            sidebarVariant="floating"
            sidebarWidth="17rem"
            brandEyebrow="Driver"
            brandSubtitle="Field kit"
            navClassNames={{
                row: 'h-10',
                icon: 'size-5',
            }}
            headerDensity="compact"
            headerAccessory={
                <div className="flex items-center gap-2">
                    <Badge variant="secondary" className="font-normal">
                        <CircleDot className="size-3" />
                        On duty
                    </Badge>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="hidden h-8 sm:inline-flex"
                    >
                        End shift
                    </Button>
                </div>
            }
            sidebarFooterExtra={
                <div className="bg-accent rounded-lg border p-3 group-data-[collapsible=icon]:hidden">
                    <div className="flex items-center gap-2 text-xs font-medium">
                        <Bike className="size-3.5" />
                        On duty
                    </div>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Shift started 07:12 · 6 stops left
                    </p>
                </div>
            }
        >
            {children}
        </PortalShellLayout>
    );
}
