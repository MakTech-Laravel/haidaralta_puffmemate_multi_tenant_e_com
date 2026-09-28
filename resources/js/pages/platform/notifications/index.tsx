import { router } from '@inertiajs/react';
import {
    destroy,
    show,
} from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { index } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { NotificationsInbox } from '@/components/portal/notifications-inbox';
import type { NotificationItem } from '@/components/notifications/types';

export default function PlatformNotificationsIndex({
    items = [],
}: {
    items?: NotificationItem[];
}) {
    return (
        <NotificationsInbox
            notifications={items}
            getItemHref={(item) => show.url(Number(item.id))}
            onDelete={(item) => {
                router.delete(destroy.url(Number(item.id)), {
                    preserveScroll: true,
                });
            }}
        />
    );
}

PlatformNotificationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: index.url(),
        },
    ],
};
