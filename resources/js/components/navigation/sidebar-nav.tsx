import { Link } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import { filterNavNodes } from '@/components/navigation/nav-utils';
import type { NavNode } from '@/components/navigation/types';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePermission } from '@/hooks/use-permissions';

type SidebarNavProps = {
    nodes: NavNode[];
    groupLabel: string;
};

export function SidebarNav({ nodes, groupLabel }: SidebarNavProps) {
    const { canAny } = usePermission();
    const { isCurrentUrl } = useCurrentUrl();
    const visibleNodes = filterNavNodes(nodes, canAny);

    if (visibleNodes.length === 0) {
        return null;
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{groupLabel}</SidebarGroupLabel>
            <SidebarMenu>
                {visibleNodes.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
