import type { OrderTrackShippingAddress } from '@/components/customer/order-track-types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type OrderTrackAddressProps = {
    address: OrderTrackShippingAddress;
};

export function OrderTrackAddress({ address }: OrderTrackAddressProps) {
    return (
        <Card className="bg-background gap-3 rounded-none border-none py-5 shadow-none">
            <CardHeader className="px-5 pb-0">
                <CardTitle className="text-foreground text-lg font-semibold sm:text-xl">
                    Shipping address
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-1 px-5 text-sm leading-relaxed">
                <p className="text-foreground font-semibold">{address.name}</p>
                {address.lines.map((line) => (
                    <p key={line} className="text-muted-foreground">
                        {line}
                    </p>
                ))}
                <p className="text-muted-foreground">{address.mobile}</p>
            </CardContent>
        </Card>
    );
}
