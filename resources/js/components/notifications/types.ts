export type NotificationType =
    | 'order'
    | 'payment'
    | 'delivery'
    | 'promo'
    | 'security';

export type NotificationItem = {
    id: string;
    title: string;
    description: string;
    createdAt: string;
    read: boolean;
    type: NotificationType;
    body?: string;
};
