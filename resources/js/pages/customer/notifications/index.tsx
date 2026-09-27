import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { NotificationList } from '@/components/notifications/notification-list';
import type { NotificationItem } from '@/components/notifications/types';
import {
    destroy as notificationsDestroy,
    index as notificationsIndex,
    show as notificationsShow,
} from '@/routes/customer/notifications';

type CustomerNotificationsIndexProps = {
    items: NotificationItem[];
};

export default function CustomerNotificationsIndex({
    items,
}: CustomerNotificationsIndexProps) {
    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-4">
                <Heading
                    title="Notifications"
                    description="Order updates and account alerts"
                />

                <NotificationList
                    items={items}
                    getItemHref={(item) => notificationsShow.url(item.id)}
                    onDelete={(item) => {
                        router.delete(notificationsDestroy.url(item.id), {
                            preserveScroll: true,
                        });
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
