import { Head } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import Heading from '@/components/heading';
import { NotificationList } from '@/components/notifications/notification-list';
import type { NotificationItem } from '@/components/notifications/types';
import { Card, CardContent } from '@/components/ui/card';

type NotificationsInboxProps = {
    notifications?: NotificationItem[];
    getItemHref: (item: NotificationItem) => string;
    onDelete?: (item: NotificationItem) => void;
};

export function NotificationsInbox({
    notifications = [],
    getItemHref,
    onDelete,
}: NotificationsInboxProps) {
    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Notifications"
                    description="Alerts for orders, staff, and system events appear here."
                />

                {notifications.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground flex flex-col items-center gap-2 py-12 text-center text-sm">
                            <Bell className="size-8 opacity-40" />
                            <p>No notifications</p>
                        </CardContent>
                    </Card>
                ) : (
                    <NotificationList
                        items={notifications}
                        getItemHref={getItemHref}
                        onDelete={onDelete}
                    />
                )}
            </div>
        </>
    );
}
