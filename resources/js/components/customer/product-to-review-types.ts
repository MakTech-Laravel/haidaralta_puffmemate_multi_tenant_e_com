export type ProductToReview = {
    id: string;
    name: string;
    image: string;
    orderId: string;
    shippingStatus: 'delivered';
    reviewed: boolean;
    viewUrl: string | null;
};
