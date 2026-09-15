import { usePage } from '@inertiajs/react';
import { NotificationsDialog } from '@/components/portal/notifications-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';

export function PortalAccountHeader() {
    const { auth } = usePage().props;

    if (!auth.user) {
        return null;
    }

    return (
        <div className="flex items-center gap-1">
            <NotificationsDialog />
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
