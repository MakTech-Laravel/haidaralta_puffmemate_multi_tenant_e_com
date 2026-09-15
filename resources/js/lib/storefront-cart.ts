export type CartBranchOption = {
    id: string;
    name: string;
};

export type CartItem = {
    id: string;
    productId: string;
    name: string;
    category: string;
    description: string;
    image: string;
    unitPrice: number;
    quantity: number;
    available: number | null;
    unavailable: boolean;
    branchId: string;
    branchName: string;
    selected: boolean;
};

export type CartGroup = {
    id: string;
    branchName: string;
    selected: boolean;
    items: CartItem[];
};

export type CartSummaryLine = {
    label: string;
    amount: number;
    tone: 'default' | 'discount' | string;
};

export type CartSummaryBranch = {
    branchName: string;
    lines: CartSummaryLine[];
};

export type CartSummary = {
    branches: CartSummaryBranch[];
    total: number;
};

export function formatMoney(amount: number): string {
    const absolute = Math.abs(amount).toFixed(2);
    return amount < 0 ? `-$${absolute}` : `$${absolute}`;
}
