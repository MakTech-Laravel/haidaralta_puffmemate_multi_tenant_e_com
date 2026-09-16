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
    has_discount: boolean;
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
    manage: string;
    transactions: string;
    price: string;
    discount: string;
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
 * Row actions for a product row (product locked) or a branch row on a product
 * page (both locked). Transactions keeps the same selection as filters.
 */
export function stockActionItems(
    urls: StockActionUrls,
    params: {
        productId?: number | null;
        branchId?: number | null;
        locked: StockLock;
    },
): Array<{ label: string; href: string }> {
    return [
        { label: 'Add stock', href: stockPageUrl(urls.manage, params) },
        {
            label: 'Stock transactions',
            href: stockPageUrl(urls.transactions, {
                ...params,
                locked: null,
            }),
        },
        { label: 'Update price', href: stockPageUrl(urls.price, params) },
        { label: 'Add discount', href: stockPageUrl(urls.discount, params) },
    ];
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
