/**
 * Badge tints. Colours come from the semantic tokens in resources/css/app.css
 * (success / warning / destructive / primary), so re-theming needs no change here.
 */
const tint = {
    muted: 'border-transparent bg-muted text-muted-foreground',
    soft: 'border-transparent bg-secondary text-foreground',
    active: 'border-transparent bg-primary/10 text-primary',
    success: 'border-transparent bg-success-tint text-success',
    warning: 'border-transparent bg-warning-tint text-warning',
    danger: 'border-transparent bg-destructive-tint text-destructive',
    outline: 'border-primary bg-secondary text-foreground',
} as const;

export function orderStatusBadgeClass(status: string): string {
    switch (status) {
        case 'pending':
        case 'hold':
            return tint.warning;
        case 'placed':
        case 'processing':
        case 'ready':
        case 'assigned':
        case 'picked_up':
        case 'out_for_delivery':
            return tint.active;
        case 'arrived':
            return tint.active;
        case 'delivered':
            return tint.success;
        case 'delivery_failed':
            return tint.danger;
        case 'returning':
        case 'returned':
            return tint.warning;
        case 'cancelled':
            return tint.muted;
        default:
            return tint.muted;
    }
}

export function orderSourceBadgeClass(source: string): string {
    switch (source) {
        case 'customer_checkout':
            return tint.active;
        case 'driver_sale':
            return tint.soft;
        case 'wholesale':
            return tint.outline;
        default:
            return tint.muted;
    }
}

export function wholesaleInquiryStatusBadgeClass(status: string): string {
    switch (status) {
        case 'new':
            return tint.active;
        case 'read':
            return tint.soft;
        case 'converted':
            return tint.success;
        case 'rejected':
            return tint.danger;
        default:
            return tint.muted;
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
        case 'cancelled':
            return 'Cancelled';
        default:
            return status.replaceAll('_', ' ');
    }
}

export function paymentStatusBadgeClass(status: string): string {
    switch (status) {
        case 'pending_collection':
            return tint.warning;
        case 'collected':
            return tint.active;
        case 'verified':
            return tint.success;
        case 'failed':
            return tint.danger;
        case 'refunded':
            return tint.warning;
        case 'cancelled':
            return tint.muted;
        default:
            return tint.muted;
    }
}
