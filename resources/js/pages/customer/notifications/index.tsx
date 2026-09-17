import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { NotificationList } from '@/components/notifications/notification-list';
import type { NotificationItem } from '@/components/notifications/types';
import {
    index as notificationsIndex,
    show as notificationsShow,
} from '@/routes/customer/notifications';

type CustomerNotificationsIndexProps = {
    notifications: NotificationItem[];
};

export default function CustomerNotificationsIndex({
    notifications: initialNotifications,
}: CustomerNotificationsIndexProps) {
    const [notifications, setNotifications] = useState(initialNotifications);

    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-4">
                <Heading
                    title="Notifications"
                    description="Order updates and account alerts"
                />

                <NotificationList
                    items={notifications}
                    getItemHref={(item) => notificationsShow.url(item.id)}
                    onDelete={(item) => {
                        setNotifications((current) =>
                            current.filter(
                                (notification) => notification.id !== item.id,
                            ),
                        );
                    }}
                />
            </div>
        </>
    );
}

CustomerNotificationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: notificationsIndex.url(),
        },
    ],
};
