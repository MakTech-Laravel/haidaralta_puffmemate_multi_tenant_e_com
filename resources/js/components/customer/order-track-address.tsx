import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { OrderTrackShippingAddress } from '@/components/customer/order-track-types';

type OrderTrackAddressProps = {
    address: OrderTrackShippingAddress;
};

export function OrderTrackAddress({ address }: OrderTrackAddressProps) {
    return (
        <Card className="gap-3 py-5 shadow-none border-none rounded-sm">
            <CardHeader className="px-5 pb-0">
                <CardTitle className="text-foreground text-lg sm:text-xl font-semibold">
                    Shipping Address
                </CardTitle>
            </CardHeader>
            <CardContent className="text-muted-foreground space-y-0.5 px-5 text-sm leading-relaxed">
                <p className="text-muted-foreground text-sm font-normal">{address.name}</p>
                {address.lines.map((line) => (
                    <p key={line} className="text-muted-foreground text-sm font-normal">{line}</p>
                ))}
                <p className="text-muted-foreground text-sm font-normal">{address.mobile}</p>
            </CardContent>
        </Card>
    );
}
