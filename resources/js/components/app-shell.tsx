import { usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant, Portal } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
    portal?: Portal;
    sidebarWidth?: string;
};

export function AppShell({
    children,
    variant = 'sidebar',
    portal,
    sidebarWidth = '16rem',
}: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div
                className="flex min-h-screen w-full flex-col"
                data-portal={portal}
            >
                {children}
            </div>
        );
    }

    return (
        <SidebarProvider
            defaultOpen={isOpen}
            data-portal={portal}
            style={
                {
                    '--sidebar-width': sidebarWidth,
                } as CSSProperties
            }
        >
            {children}
        </SidebarProvider>
    );
}
