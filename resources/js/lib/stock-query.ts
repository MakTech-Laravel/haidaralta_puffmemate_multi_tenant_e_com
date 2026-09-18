export type StockLock = 'product' | 'branch' | 'both';

export type StockOption = {
    id: number;
    name: string;
    slug: string;
    sku?: string | null;
};

export type StockPrice = {
    selling_price: string | null;
    effective_price: string | null;
    regular_price?: string | null;
    has_discount: boolean;
    has_compare_at?: boolean;
    discount_type: string;
    discount_price: string | null;
    start_date?: string | null;
    end_date?: string | null;
};

export type StockPageContext = {
    locked: StockLock | null;
    branch: StockOption | null;
    product: (StockOption & { sku: string | null }) | null;
    inventory: { quantity: number } | null;
    price: StockPrice | null;
    branch_options: StockOption[];
    product_options: Array<StockOption & { sku: string | null }>;
};

export function stockSelectionQuery(
    context: Pick<StockPageContext, 'locked' | 'branch' | 'product'>,
    overrides: { branchId?: number | null; productId?: number | null } = {},
): Record<string, string | number> {
    const branchId =
        overrides.branchId === undefined
            ? context.branch?.id
            : overrides.branchId;
    const productId =
        overrides.productId === undefined
            ? context.product?.id
            : overrides.productId;
    const query: Record<string, string | number> = {};

    if (branchId) {
        query.branch = branchId;
    }

    if (productId) {
        query.product = productId;
    }

    if (context.locked) {
        query.locked = context.locked;
    }

    return query;
}

export type StockActionUrls = {
    manage?: string;
    transactions: string;
    priceHistory: string;
};

export function stockPageUrl(
    base: string,
    params: {
        productId?: number | null;
        branchId?: number | null;
        locked?: StockLock | null;
    },
): string {
    const query = new URLSearchParams();

    if (params.productId) {
        query.set('product', String(params.productId));
    }

    if (params.branchId) {
        query.set('branch', String(params.branchId));
    }

    if (params.locked) {
        query.set('locked', params.locked);
    }

    const search = query.toString();

    return search === '' ? base : `${base}?${search}`;
}

/**
 * Stock group row actions. Daily stock, selling price, and discounts stay on
 * Manage stock. Ledger and price history keep the same product/branch query
 * (`locked` omitted). Do not add Add stock, Update price, or Add discount.
 */
export function stockActionItems(
    urls: StockActionUrls,
    params: {
        productId?: number | null;
        branchId?: number | null;
    },
): Array<{ label: string; href: string }> {
    const items: Array<{ label: string; href: string }> = [];

    if (urls.manage) {
        items.push({
            label: 'Manage stock',
            href: urls.manage,
        });
    }

    items.push(
        {
            label: 'Stock transactions',
            href: stockPageUrl(urls.transactions, params),
        },
        {
            label: 'Price history',
            href: stockPageUrl(urls.priceHistory, params),
        },
    );

    return items;
}

export function isBranchLocked(locked: StockLock | null): boolean {
    return locked === 'branch' || locked === 'both';
}

export function isProductLocked(locked: StockLock | null): boolean {
    return locked === 'product' || locked === 'both';
}

export type StockOptionPage = {
    data: Array<{
        id: number;
        name: string;
        slug?: string;
        sku?: string | null;
    }>;
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

export async function fetchStockOptions(
    optionsUrl: string,
    args: {
        search?: string;
        page?: number;
        perPage?: number;
        signal?: AbortSignal;
    } = {},
): Promise<StockOptionPage> {
    const url = new URL(optionsUrl, window.location.origin);

    if (args.search) {
        url.searchParams.set('filter[search]', args.search);
    }

    url.searchParams.set('page', String(args.page ?? 1));
    url.searchParams.set('per_page', String(args.perPage ?? 20));

    const response = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal: args.signal,
    });

    if (!response.ok) {
        throw new Error('Failed to load options');
    }

    return (await response.json()) as StockOptionPage;
}
