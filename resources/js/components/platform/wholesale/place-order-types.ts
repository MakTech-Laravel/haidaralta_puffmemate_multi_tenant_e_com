export type WholesalePlaceOrderInquiry = {
    id: number;
    full_name: string;
    phone: string;
    company: string | null;
    ask_question: string | null;
    email: string | null;
    address: string;
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
    note: string;
};
