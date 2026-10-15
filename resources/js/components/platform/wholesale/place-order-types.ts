export type WholesalePlaceOrderInquiryItem = {
    id: number;
    product_id: number;
    variant_id: number | null;
    name: string;
    sku: string | null;
    quantity: number;
};

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
    message: string | null;
    status: string;
    status_label: string;
    is_open: boolean;
    read_at: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
    customer: { id: number; name: string; phone: string | null } | null;
    items: WholesalePlaceOrderInquiryItem[];
};

export type WholesalePlaceOrderBranch = {
    id: number;
    name: string;
    slug: string;
};

export type WholesalePlaceOrderProduct = {
    /** `{product}` or `{product}:{variant}`; unique per branch stock row. */
    id: string;
    product_id: number;
    variant_id: number | null;
    branch_id: number;
    name: string;
    sku: string;
    price: number;
    min_quantity: number | null;
    is_wholesale_price: boolean;
    stock: number;
    image: string | null;
};

export type WholesalePlaceOrderLine = {
    /** Matches `WholesalePlaceOrderProduct.id`. */
    key: string;
    productId: string;
    variantId: number | null;
    name: string;
    sku: string;
    price: number;
    stock: number;
    quantity: number;
    minQuantity?: number | null;
    /** True once staff typed a unit price; branch switches keep it. */
    priceEdited?: boolean;
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
