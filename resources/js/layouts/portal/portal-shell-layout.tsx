import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import AppLogo from '@/components/app-logo';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { SidebarNav } from '@/components/navigation/sidebar-nav';
import type { NavNode } from '@/components/navigation/types';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type PortalShellLayoutProps = PropsWithChildren<{
    breadcrumbs?: BreadcrumbItem[];
    groupLabel: string;
    logoHref: string;
    nodes: NavNode[];
}>;

export function PortalShellLayout({
    breadcrumbs = [],
    children,
    groupLabel,
    logoHref,
    nodes,
}: PortalShellLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <Sidebar collapsible="icon" variant="inset">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={logoHref} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent>
                    <SidebarNav nodes={nodes} groupLabel={groupLabel} />
                </SidebarContent>

                <SidebarFooter>
                    <NavUser />
                </SidebarFooter>
            </Sidebar>

            <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
