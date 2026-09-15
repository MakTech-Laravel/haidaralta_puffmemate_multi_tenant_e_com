import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { index as branchNotifications } from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { index as platformNotifications } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

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

export function NotificationsDialog() {
    const { portal, branch } = usePage().props;
    const viewAllHref = notificationsIndexUrl(portal, branch?.slug);

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9"
                    aria-label="Notifications"
                    data-test="notifications-bell"
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 ? (
                        <span className="bg-primary absolute top-1.5 right-1.5 size-2 rounded-full" />
                    ) : null}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Notifications</DialogTitle>
                    <DialogDescription>
                        You have no notifications yet. Alerts for orders, staff,
                        and system events will appear here.
                    </DialogDescription>
                </DialogHeader>
                <p className="text-muted-foreground py-6 text-center text-sm">
                    No notifications
                </p>
                {viewAllHref ? (
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline" asChild>
                                <Link href={viewAllHref} prefetch>
                                    View all
                                </Link>
                            </Button>
                        </DialogClose>
                    </DialogFooter>
                ) : null}
            </DialogContent>
        </Dialog>
    );
}
