import { index } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { NotificationsInbox } from '@/components/portal/notifications-inbox';

export default function PlatformNotificationsIndex() {
    return <NotificationsInbox />;
}

PlatformNotificationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: index.url(),
        },
    ],
};
