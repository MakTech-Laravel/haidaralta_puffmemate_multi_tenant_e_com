import { usePage } from '@inertiajs/react';
import { Radio } from 'lucide-react';
import { NotificationsPopover } from '@/components/portal/notifications-popover';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { home } from '@/routes';

export function PortalAccountHeader() {
    const { auth } = usePage().props;

    if (!auth.user) {
        return null;
    }

    return (
        <div className="flex items-center gap-1">
            <Button
                variant="ghost"
                size="icon"
                className="text-primary hover:text-primary relative h-9 w-9"
                asChild
            >
                <a
                    href={home.url()}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Open live storefront"
                    title="Open live storefront"
                    data-test="portal-live-storefront"
                >
                    <span className="relative inline-flex">
                        <Radio className="size-4" strokeWidth={1.75} />
                        <span
                            aria-hidden
                            className="bg-primary absolute -top-0.5 -right-0.5 size-1.5 rounded-full"
                        />
                    </span>
                </a>
            </Button>
            <NotificationsPopover />
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        className="h-9 gap-2 px-1.5"
                        data-test="portal-account-menu"
                    >
                        <UserInfo user={auth.user} />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56" align="end">
                    <UserMenuContent user={auth.user} />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
