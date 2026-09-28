export type NotificationType =
    | 'order'
    | 'payment'
    | 'delivery'
    | 'promo'
    | 'security'
    | 'info'
    | 'alert'
    | 'update';

export type NotificationItem = {
    id: string;
    title: string;
    description: string;
    createdAt: string;
    read: boolean;
    type: NotificationType;
    body?: string;
    icon?: string | null;
    href?: string | null;
    linkLabel?: string | null;
};

export type SharedNotifications = {
    unreadCount: number;
    preview: NotificationItem[];
};
