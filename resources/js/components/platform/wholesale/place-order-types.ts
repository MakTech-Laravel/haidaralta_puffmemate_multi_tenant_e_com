export type WholesalePlaceOrderInquiry = {
    id: number;
    full_name: string;
    phone: string;
    company: string | null;
    email: string | null;
    address: string;
    latitude: string | null;
    longitude: string | null;
    map: {
        google: string;
        open_street_map: string;
        embed: string;
    } | null;
    message: string;
    status: string;
    status_label: string;
    read_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
};

export type WholesalePlaceOrderBranch = {
    id: number;
    name: string;
    slug: string;
};

export type WholesalePlaceOrderProduct = {
    id: string;
    branch_id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    image: string | null;
};

export type WholesalePlaceOrderLine = {
    key: string;
    productId: string;
    name: string;
    sku: string;
    price: number;
    stock: number;
    quantity: number;
};

export type WholesalePlaceOrderCustomerForm = {
    full_name: string;
    phone: string;
    email: string;
    company: string;
    address: string;
    latitude: string;
    longitude: string;
    note: string;
};
