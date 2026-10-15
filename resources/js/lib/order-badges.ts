const tint = {
    amber: 'border-transparent bg-amber-500/15 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
    sky: 'border-transparent bg-sky-500/15 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300',
    blue: 'border-transparent bg-blue-500/15 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
    indigo: 'border-transparent bg-indigo-500/15 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300',
    violet: 'border-transparent bg-violet-500/15 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300',
    teal: 'border-transparent bg-teal-500/15 text-teal-800 dark:bg-teal-500/20 dark:text-teal-300',
    cyan: 'border-transparent bg-cyan-500/15 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300',
    orange: 'border-transparent bg-orange-500/15 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300',
    emerald:
        'border-transparent bg-emerald-500/15 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
    rose: 'border-transparent bg-rose-500/15 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
    zinc: 'border-transparent bg-zinc-500/15 text-zinc-700 dark:bg-zinc-500/20 dark:text-zinc-300',
} as const;

export function orderStatusBadgeClass(status: string): string {
    switch (status) {
        case 'pending':
        case 'hold':
            return tint.amber;
        case 'placed':
            return tint.teal;
        case 'processing':
            return tint.sky;
        case 'ready':
            return tint.cyan;
        case 'assigned':
            return tint.indigo;
        case 'picked_up':
            return tint.violet;
        case 'out_for_delivery':
            return tint.blue;
        case 'arrived':
            return tint.emerald;
        case 'delivery_failed':
            return tint.rose;
        case 'returning':
        case 'returned':
            return tint.orange;
        case 'delivered':
            return tint.emerald;
        case 'cancelled':
            return tint.zinc;
        default:
            return tint.zinc;
    }
}

export function orderSourceBadgeClass(source: string): string {
    switch (source) {
        case 'customer_checkout':
            return tint.sky;
        case 'driver_sale':
            return tint.violet;
        case 'wholesale':
            return tint.teal;
        default:
            return tint.zinc;
    }
}

export function wholesaleInquiryStatusBadgeClass(status: string): string {
    switch (status) {
        case 'new':
            return tint.amber;
        case 'read':
            return tint.sky;
        case 'converted':
            return tint.emerald;
        case 'rejected':
            return tint.rose;
        default:
            return tint.zinc;
    }
}

export function paymentStatusLabel(status: string): string {
    switch (status) {
        case 'pending_collection':
            return 'Pending collection';
        case 'collected':
            return 'Collected';
        case 'verified':
            return 'Verified';
        case 'failed':
            return 'Failed';
        case 'refunded':
            return 'Refunded';
        default:
            return status.replaceAll('_', ' ');
    }
}

export function paymentStatusBadgeClass(status: string): string {
    switch (status) {
        case 'pending_collection':
            return tint.amber;
        case 'collected':
            return tint.emerald;
        case 'verified':
            return tint.teal;
        case 'failed':
            return tint.rose;
        case 'refunded':
            return tint.violet;
        default:
            return tint.zinc;
    }
}
