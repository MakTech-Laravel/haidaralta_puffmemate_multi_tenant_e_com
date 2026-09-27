import { Head } from '@inertiajs/react';
import { NotificationDetail } from '@/components/notifications/notification-detail';
import type { NotificationItem } from '@/components/notifications/types';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Platform/NotificationController';

type PlatformNotificationShowProps = {
    notification: NotificationItem;
};

export default function PlatformNotificationShow({
    notification,
}: PlatformNotificationShowProps) {
    return (
        <>
            <Head title={notification.title} />
            <div className="p-2 md:p-4">
                <NotificationDetail notification={notification} />
            </div>
        </>
    );
}

PlatformNotificationShow.layout = (props: PlatformNotificationShowProps) => ({
    breadcrumbs: [
        {
            title: 'Notifications',
            href: index.url(),
        },
        {
            title: props.notification.title,
            href: show.url(Number(props.notification.id)),
        },
    ],
});
