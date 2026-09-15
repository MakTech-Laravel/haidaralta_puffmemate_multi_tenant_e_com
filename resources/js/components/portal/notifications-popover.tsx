import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useState } from 'react';
import { index as branchNotifications } from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { index as platformNotifications } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const unreadCount = 0;

function notificationsIndexUrl(
    portal: string | null,
    branchSlug?: string | null,
): string | null {
    if (portal === 'platform') {
        return platformNotifications.url();
    }

    if (portal === 'branch' && branchSlug) {
        return branchNotifications.url(branchSlug);
    }

    return null;
}

export function NotificationsPopover() {
    const { portal, branch } = usePage().props;
    const [open, setOpen] = useState(false);
    const viewAllHref = notificationsIndexUrl(portal, branch?.slug);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9"
                    aria-label="Notifications"
                    aria-expanded={open}
                    data-test="notifications-bell"
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 ? (
                        <span className="bg-primary absolute top-1.5 right-1.5 size-2 rounded-full" />
                    ) : null}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                align="end"
                side="bottom"
                sideOffset={8}
                className="w-80 p-0"
            >
                <div className="px-3 py-2.5">
                    <p className="text-sm font-medium">Notifications</p>
                    <p className="text-muted-foreground text-xs">
                        Alerts for orders, staff, and system events appear here.
                    </p>
                </div>
                <div className="text-muted-foreground border-t px-3 py-8 text-center text-sm">
                    No notifications
                </div>
                {viewAllHref ? (
                    <div className="border-t p-1.5">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full"
                            asChild
                        >
                            <Link
                                href={viewAllHref}
                                prefetch
                                onClick={() => setOpen(false)}
                            >
                                View all
                            </Link>
                        </Button>
                    </div>
                ) : null}
            </PopoverContent>
        </Popover>
    );
}
