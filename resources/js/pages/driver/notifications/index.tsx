import { Head, router } from '@inertiajs/react';
import {
    destroy,
    index,
    show,
} from '@/actions/App/Http/Controllers/Driver/NotificationController';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import Heading from '@/components/heading';
import { NotificationList } from '@/components/notifications/notification-list';
import type { NotificationItem } from '@/components/notifications/types';
import { Bell } from 'lucide-react';

export default function DriverNotificationsIndex({
    items = [],
    unreadCount = 0,
}: {
    items?: NotificationItem[];
    unreadCount?: number;
}) {
    return (
        <>
            <Head title="Notifications" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Notifications"
                    description={
                        unreadCount > 0
                            ? `${unreadCount} unread`
                            : 'Stop assignments and delivery alerts'
                    }
                />

                {items.length === 0 ? (
                    <DriverEmptyState
                        icon={Bell}
                        title="You're all caught up"
                        description="New stops, OTP reminders, and branch messages will land here."
                    />
                ) : (
                    <NotificationList
                        items={items}
                        getItemHref={(item) => show.url(Number(item.id))}
                        onDelete={(item) => {
                            router.delete(destroy.url(Number(item.id)), {
                                preserveScroll: true,
                            });
                        }}
                    />
                )}
            </div>
        </>
    );
}

DriverNotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifications', href: index.url() }],
};
