import { Head } from '@inertiajs/react';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Driver/NotificationController';
import { NotificationDetail } from '@/components/notifications/notification-detail';
import type { NotificationItem } from '@/components/notifications/types';

type DriverNotificationShowProps = {
    notification: NotificationItem;
};

export default function DriverNotificationShow({
    notification,
}: DriverNotificationShowProps) {
    return (
        <>
            <Head title={notification.title} />
            <NotificationDetail notification={notification} />
        </>
    );
}

DriverNotificationShow.layout = (props: DriverNotificationShowProps) => ({
    breadcrumbs: [
        { title: 'Notifications', href: index.url() },
        {
            title: props.notification.title,
            href: show.url(Number(props.notification.id)),
        },
    ],
});
