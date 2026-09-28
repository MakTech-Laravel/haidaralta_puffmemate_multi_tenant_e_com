import type { OrderTrackProduct } from '@/components/customer/order-track-types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type OrderTrackProductsProps = {
    products: OrderTrackProduct[];
    totalLabel: string;
};

export function OrderTrackProducts({
    products,
    totalLabel,
}: OrderTrackProductsProps) {
    return (
        <Card className="gap-4 rounded-xs border-none py-5 shadow-none">
            <CardHeader className="px-5 pb-0">
                <CardTitle className="text-foreground text-lg font-semibold sm:text-xl">
                    Items
                </CardTitle>
            </CardHeader>
            <CardContent className="px-5">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead className="text-muted-foreground h-9 px-0 text-sm font-normal">
                                Product Name
                            </TableHead>
                            <TableHead className="text-muted-foreground h-9 px-0 text-sm font-normal">
                                Quantity
                            </TableHead>
                            <TableHead className="text-muted-foreground h-9 px-0 text-right text-sm font-normal">
                                Price
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.map((product) => (
                            <TableRow
                                key={`${product.name}-${product.quantityLabel}`}
                                className="hover:bg-transparent"
                            >
                                <TableCell className="text-foreground px-0 py-3 text-sm font-normal whitespace-normal">
                                    {product.name}
                                </TableCell>
                                <TableCell className="text-foreground px-0 py-3 text-sm font-normal">
                                    {product.quantityLabel}
                                </TableCell>
                                <TableCell className="text-foreground px-0 py-3 text-right text-sm font-normal">
                                    {product.priceLabel}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <Separator className="my-4" />

                <div className="flex items-center justify-between gap-4">
                    <span className="text-foreground text-xl font-bold">
                        Total
                    </span>
                    <span className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        {totalLabel}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
