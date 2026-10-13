import { Badge } from '@/components/ui/badge';
import { wholesaleInquiryStatusBadgeClass } from '@/lib/order-badges';
import { cn } from '@/lib/utils';

type WholesaleInquiryStatusBadgeProps = {
    status: string;
    label: string;
    className?: string;
};

export function WholesaleInquiryStatusBadge({
    status,
    label,
    className,
}: WholesaleInquiryStatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(wholesaleInquiryStatusBadgeClass(status), className)}
        >
            {label}
        </Badge>
    );
}
