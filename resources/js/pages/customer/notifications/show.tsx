import { Head } from '@inertiajs/react';
import { NotificationDetail } from '@/components/notifications/notification-detail';
import type { NotificationItem } from '@/components/notifications/types';
import {
    index as notificationsIndex,
    show as notificationsShow,
} from '@/routes/customer/notifications';

type CustomerNotificationShowProps = {
    notification: NotificationItem;
};

export default function CustomerNotificationShow({
    notification,
}: CustomerNotificationShowProps) {
    return (
        <>
            <Head title={notification.title} />

            <NotificationDetail notification={notification} />
        </>
    );
}

CustomerNotificationShow.layout = (props: CustomerNotificationShowProps) => ({
    breadcrumbs: [
        {
            title: 'Notifications',
            href: notificationsIndex.url(),
        },
        {
            title: props.notification.title,
            href: notificationsShow.url(props.notification.id),
        },
    ],
});
