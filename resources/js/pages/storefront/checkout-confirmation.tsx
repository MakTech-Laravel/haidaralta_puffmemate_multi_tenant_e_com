import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { shop } from '@/routes';

type ConfirmationProps = {
    order: {
        id: number;
        orderNumber: string;
        total: string;
        paymentMethod: string;
        itemCount: number;
    };
};

export default function StorefrontCheckoutConfirmation({
    order,
}: ConfirmationProps) {
    return (
        <>
            <Head title="Order confirmed" />

            <div className="flex-1">
                <div className="container mx-auto max-w-xl px-4 py-12 sm:px-6">
                    <h1 className="text-foreground text-3xl font-bold tracking-tight">
                        Order placed
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        We received order {order.orderNumber}. Pay {order.total}{' '}
                        on delivery ({order.paymentMethod}).
                    </p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {order.itemCount}{' '}
                        {order.itemCount === 1 ? 'item' : 'items'} in this
                        order.
                    </p>
                    <Button asChild className="mt-6">
                        <Link href={shop.url()}>Continue shopping</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
