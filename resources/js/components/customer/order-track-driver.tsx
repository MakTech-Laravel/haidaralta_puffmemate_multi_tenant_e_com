import { Phone, Truck } from 'lucide-react';
import type { OrderTrackDriver } from '@/components/customer/order-track-types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type OrderTrackDriverProps = {
    driver: OrderTrackDriver;
};

export function OrderTrackDriverCard({ driver }: OrderTrackDriverProps) {
    return (
        <Card className="gap-0 border-0 bg-[#F4F4F2] py-4 shadow-none">
            <CardContent className="flex items-start gap-3 px-4">
                <div className="bg-foreground text-background flex size-11 shrink-0 items-center justify-center rounded-full">
                    <Truck className="size-5" aria-hidden />
                </div>

                <div className="min-w-0 flex-1 space-y-2">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="text-foreground truncate text-sm font-semibold">
                                {driver.name}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {driver.truckNumber}
                            </p>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            className="h-8 shrink-0 rounded-md px-3 text-xs font-semibold"
                        >
                            Chat
                        </Button>
                    </div>

                    <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                        <Phone className="size-3.5 shrink-0" aria-hidden />
                        <span>{driver.phone}</span>
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
