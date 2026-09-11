import { usePage } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { customerNav } from '@/components/customer/nav';
import { Input } from '@/components/ui/input';
import { PortalShellLayout } from '@/layouts/portal/portal-shell-layout';
import type { Auth, BreadcrumbItem } from '@/types';

export default function CustomerLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user?.name.split(/\s+/u)[0] ?? 'there';

    return (
        <PortalShellLayout
            portal="customer"
            breadcrumbs={breadcrumbs}
            groupLabel="Shop"
            groupLabelClassName="text-sm font-medium"
            logoHref="/dashboard"
            nodes={customerNav}
            sidebarVariant="inset"
            sidebarWidth="18.5rem"
            brandEyebrow="Account"
            brandSubtitle={`Hi, ${firstName}`}
            navClassNames={{
                row: 'h-9',
            }}
            headerDensity="airy"
            headerAccessory={
                <Input
                    readOnly
                    placeholder="Search orders and products"
                    aria-label="Search (placeholder)"
                    className="bg-muted/60 hidden h-9 w-56 shadow-none md:flex"
                />
            }
            sidebarBanner={
                <div className="bg-accent mx-2 mb-2 rounded-xl p-3 group-data-[collapsible=icon]:hidden">
                    <div className="flex items-center gap-2 text-xs font-medium">
                        <Sparkles className="size-3.5" />
                        Weekend pickup
                    </div>
                    <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                        Free on demo orders over $25. Placeholder offer.
                    </p>
                </div>
            }
        >
            {children}
        </PortalShellLayout>
    );
}
