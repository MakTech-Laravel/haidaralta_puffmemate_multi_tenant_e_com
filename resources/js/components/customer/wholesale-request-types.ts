export type CustomerWholesaleRequest = {
    id: number;
    status: string;
    statusLabel: string;
    itemCount: number;
    orderNumber: string | null;
    date: string;
};

export type CustomerWholesaleRequestDetail = CustomerWholesaleRequest & {
    company: string | null;
    phone: string;
    email: string | null;
    address: string | null;
    message: string | null;
    rejectionReason: string | null;
    items: {
        name: string;
        sku: string | null;
        quantity: number;
    }[];
};
