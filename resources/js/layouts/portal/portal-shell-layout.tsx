import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { SidebarNav } from '@/components/navigation/sidebar-nav';
import type {
    NavNode,
    NavNodeClassNames,
    NavSection,
} from '@/components/navigation/types';
import { NavUser } from '@/components/nav-user';
import { PortalAccountHeader } from '@/components/portal/portal-account-header';
import { PortalBrand } from '@/components/portal/portal-brand';
import { PortalSidebarHeader } from '@/components/portal/portal-sidebar-header';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, Portal } from '@/types';

export type PortalShellLayoutProps = {
    brandEyebrow?: string;
    brandSubtitle?: string;
    brandTone?: 'default' | 'inverse';
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
    groupLabel?: string;
    groupLabelClassName?: string;
    headerAccessory?: ReactNode;
    headerAccent?: 'none' | 'primary-bar';
    headerBanner?: ReactNode;
    headerDensity?: 'compact' | 'default' | 'airy';
    logoHref: string;
    navClassName?: string;
    navClassNames?: NavNodeClassNames;
    /** Flat tree + optional heading. Used by the driver portal. */
    nodes?: NavNode[];
    /** Labeled blocks. Used by platform and branch. Takes precedence over `nodes`. */
    sections?: NavSection[];
    portal: Portal;
    sidebarBanner?: ReactNode;
    sidebarClassName?: string;
    sidebarCollapsible?: 'offcanvas' | 'icon' | 'none';
    sidebarContentClassName?: string;
    sidebarFooterClassName?: string;
    sidebarFooterExtra?: ReactNode;
    sidebarHeaderClassName?: string;
    sidebarVariant: 'sidebar' | 'floating' | 'inset';
    sidebarWidth?: string;
};

export function PortalShellLayout({
    brandEyebrow,
    brandSubtitle,
    brandTone = 'default',
    breadcrumbs = [],
    children,
    groupLabel,
    groupLabelClassName,
    headerAccessory,
    headerAccent = 'none',
    headerBanner,
    headerDensity = 'default',
    logoHref,
    navClassName,
    navClassNames,
    nodes,
    sections,
    portal,
    sidebarBanner,
    sidebarClassName,
    sidebarCollapsible = 'icon',
    sidebarContentClassName = 'scrollbar-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
    sidebarFooterClassName,
    sidebarFooterExtra,
    sidebarHeaderClassName,
    sidebarVariant,
    sidebarWidth = '16rem',
}: PortalShellLayoutProps) {
    const showAccountChrome = portal === 'platform' || portal === 'branch';
    const headerRight =
        headerAccessory || showAccountChrome ? (
            <>
                {headerAccessory}
                {showAccountChrome ? <PortalAccountHeader /> : null}
            </>
        ) : undefined;

    return (
        <AppShell variant="sidebar" portal={portal} sidebarWidth={sidebarWidth}>
            <Sidebar
                collapsible={sidebarCollapsible}
                variant={sidebarVariant}
                {...(sidebarClassName ? { className: sidebarClassName } : {})}
            >
                <SidebarHeader
                    {...(sidebarHeaderClassName
                        ? { className: sidebarHeaderClassName }
                        : {})}
                >
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                size="lg"
                                asChild
                                className={cn(
                                    brandTone === 'inverse' &&
                                        'hover:text-primary-foreground hover:bg-white/10 active:bg-white/10',
                                )}
                            >
                                <Link href={logoHref} prefetch>
                                    <PortalBrand
                                        tone={brandTone}
                                        {...(brandEyebrow
                                            ? { eyebrow: brandEyebrow }
                                            : {})}
                                        {...(brandSubtitle
                                            ? { subtitle: brandSubtitle }
                                            : {})}
                                    />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                {sidebarBanner}

                <SidebarContent
                    {...(sidebarContentClassName
                        ? { className: sidebarContentClassName }
                        : {})}
                >
                    {(
                        sections ?? [
                            {
                                label: groupLabel,
                                items: nodes ?? [],
                            },
                        ]
                    ).map((section, index) => (
                        <SidebarNav
                            key={section.label ?? `section-${index}`}
                            items={section.items}
                            {...(section.label ? { label: section.label } : {})}
                            {...(navClassName
                                ? { className: navClassName }
                                : {})}
                            {...(groupLabelClassName
                                ? { labelClassName: groupLabelClassName }
                                : {})}
                            {...(navClassNames
                                ? { classNames: navClassNames }
                                : {})}
                        />
                    ))}
                </SidebarContent>

                <SidebarFooter
                    {...(sidebarFooterClassName
                        ? { className: sidebarFooterClassName }
                        : {})}
                >
                    {sidebarFooterExtra}
                    <NavUser />
                </SidebarFooter>
            </Sidebar>

            <AppContent
                variant="sidebar"
                className={cn(
                    'min-w-0 overflow-hidden',
                    sidebarVariant === 'inset'
                        ? 'max-h-svh md:max-h-[calc(100svh-1rem)]'
                        : 'h-svh',
                )}
            >
                <PortalSidebarHeader
                    breadcrumbs={breadcrumbs}
                    density={headerDensity}
                    accent={headerAccent}
                    sticky={showAccountChrome}
                    {...(headerRight ? { accessory: headerRight } : {})}
                    {...(headerBanner ? { banner: headerBanner } : {})}
                />
                <div className="min-h-0 flex-1 overflow-x-clip overflow-y-auto">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
