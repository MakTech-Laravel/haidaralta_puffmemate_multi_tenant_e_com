import { usePage } from '@inertiajs/react';
import { show as branchShow } from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { index as branchNotifications } from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { show as platformShow } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { index as platformNotifications } from '@/actions/App/Http/Controllers/Platform/NotificationController';
import { NotificationsPopover as InboxPreview } from '@/components/notifications/notifications-popover';
import type { NotificationItem } from '@/components/notifications/types';

export function NotificationsPopover() {
    const { portal, branch, notifications } = usePage().props;
    const preview = notifications?.preview ?? [];
    const viewAllHref =
        portal === 'platform'
            ? platformNotifications.url()
            : portal === 'branch' && branch?.slug
              ? branchNotifications.url(branch.slug)
              : null;

    const getItemHref = (item: NotificationItem): string => {
        if (portal === 'platform') {
            return platformShow.url(Number(item.id));
        }

        if (portal === 'branch' && branch?.slug) {
            return branchShow.url({
                branch: branch.slug,
                userNotification: Number(item.id),
            });
        }

        return viewAllHref ?? '#';
    };

    if (!viewAllHref) {
        return null;
    }

    return (
        <InboxPreview
            items={preview}
            viewAllHref={viewAllHref}
            getItemHref={getItemHref}
            triggerClassName="h-9 w-9"
        />
    );
}
