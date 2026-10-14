import { Form, Link } from '@inertiajs/react';
import {
    KeyRound,
    MapPinCheck,
    PackageCheck,
    Truck,
    Undo2,
    type LucideIcon,
} from 'lucide-react';
import { toast } from 'sonner';
import OrderController from '@/actions/App/Http/Controllers/Driver/OrderController';
import type {
    DriverOrderNextAction as DriverOrderNextActionData,
    DriverOrderNextActionKey,
} from '@/components/driver/driver-order-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { show as orderShow } from '@/routes/driver/orders';

const actionIcons: Record<DriverOrderNextActionKey, LucideIcon> = {
    pick_up: PackageCheck,
    start: Truck,
    arrive: MapPinCheck,
    complete: KeyRound,
    return: Undo2,
};

const stepForms = {
    pick_up: OrderController.pickUp.form,
    start: OrderController.start.form,
    arrive: OrderController.arrive.form,
    return: OrderController.beginReturn.form,
} as const;

type DriverOrderNextActionProps = {
    orderId: string;
    action: DriverOrderNextActionData;
    className?: string;
};

export function DriverOrderNextAction({
    orderId,
    action,
    className,
}: DriverOrderNextActionProps) {
    const Icon = actionIcons[action.key];
    const variant = action.key === 'return' ? 'outline' : 'default';
    const buttonClassName = cn('h-10 w-full', className);

    if (action.key === 'complete') {
        return (
            <Button size="lg" className={buttonClassName} asChild>
                <Link
                    href={orderShow.url(orderId)}
                    prefetch
                    data-test="driver-order-next-action"
                >
                    <Icon data-icon="inline-start" />
                    {action.label}
                </Link>
            </Button>
        );
    }

    return (
        <Form
            {...stepForms[action.key](orderId)}
            options={{ preserveScroll: true }}
            showProgress={false}
            onError={(errors) => {
                toast.error(
                    errors.status ?? 'This order could not be updated.',
                );
            }}
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    size="lg"
                    variant={variant}
                    className={buttonClassName}
                    disabled={processing}
                    data-test="driver-order-next-action"
                >
                    {processing ? (
                        <Spinner data-icon="inline-start" />
                    ) : (
                        <Icon data-icon="inline-start" />
                    )}
                    {action.label}
                </Button>
            )}
        </Form>
    );
}
