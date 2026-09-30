import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type Slot = {
    id: number;
    label: string;
    status: boolean;
};

type DeliveryMethodRow = {
    id: number;
    key: string;
    label: string;
    description: string | null;
    fee: string;
    requires_slot: boolean;
    status: boolean;
    sort: number;
    slots: Slot[];
};

type BranchShippingRuleRow = {
    id: number;
    branch_id: number;
    branch_name: string;
    base_fee: string;
    free_delivery_distance_km: string;
    cost_per_km: string;
    minimum_delivery_charge: string;
    status: boolean;
};

function DeliveryMethodForm({ method }: { method: DeliveryMethodRow }) {
    const form = useForm({
        label: method.label,
        description: method.description ?? '',
        fee: method.fee,
        requires_slot: method.requires_slot,
        status: method.status,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/platform/delivery-settings/${method.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <form className={settingsCardClass} onSubmit={submit}>
            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 className="text-foreground text-base font-semibold">
                        {method.label}
                    </h2>
                    <p className="text-muted-foreground text-xs">
                        {method.key}
                    </p>
                </div>
                <Switch
                    checked={form.data.status}
                    onCheckedChange={(checked) =>
                        form.setData('status', checked)
                    }
                    aria-label={`Toggle ${method.label}`}
                />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor={`label-${method.id}`}>Label</Label>
                    <Input
                        id={`label-${method.id}`}
                        value={form.data.label}
                        onChange={(event) =>
                            form.setData('label', event.target.value)
                        }
                    />
                    <InputError message={form.errors.label} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`fee-${method.id}`}>Fee</Label>
                    <Input
                        id={`fee-${method.id}`}
                        type="number"
                        min={0}
                        step="0.01"
                        value={form.data.fee}
                        onChange={(event) =>
                            form.setData('fee', event.target.value)
                        }
                    />
                    <InputError message={form.errors.fee} />
                </div>
            </div>
            <div className="mt-4 space-y-2">
                <Label htmlFor={`description-${method.id}`}>Description</Label>
                <Input
                    id={`description-${method.id}`}
                    value={form.data.description}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                />
                <InputError message={form.errors.description} />
            </div>
            <div className="mt-4 flex items-center justify-between rounded-xl border p-3">
                <Label htmlFor={`slot-${method.id}`}>Requires time slot</Label>
                <Switch
                    id={`slot-${method.id}`}
                    checked={form.data.requires_slot}
                    onCheckedChange={(checked) =>
                        form.setData('requires_slot', checked)
                    }
                />
            </div>
            {method.slots.length > 0 ? (
                <ul className="text-muted-foreground mt-4 list-disc pl-5 text-sm">
                    {method.slots.map((slot) => (
                        <li key={slot.id}>{slot.label}</li>
                    ))}
                </ul>
            ) : null}
            <Button type="submit" className="mt-4" disabled={form.processing}>
                Save method
            </Button>
        </form>
    );
}

function BranchShippingRuleForm({ rule }: { rule: BranchShippingRuleRow }) {
    const form = useForm({
        base_fee: rule.base_fee,
        free_delivery_distance_km: rule.free_delivery_distance_km,
        cost_per_km: rule.cost_per_km,
        minimum_delivery_charge: rule.minimum_delivery_charge,
        status: rule.status,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/platform/delivery-settings/shipping-rules/${rule.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <form className={settingsCardClass} onSubmit={submit}>
            <div className="mb-4 flex items-center justify-between gap-3">
                <h3 className="text-foreground text-base font-semibold">
                    {rule.branch_name}
                </h3>
                <Switch
                    checked={form.data.status}
                    onCheckedChange={(checked) =>
                        form.setData('status', checked)
                    }
                    aria-label={`Toggle shipping for ${rule.branch_name}`}
                />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor={`base-fee-${rule.id}`}>Base fee</Label>
                    <Input
                        id={`base-fee-${rule.id}`}
                        type="number"
                        min={0}
                        step="0.01"
                        value={form.data.base_fee}
                        onChange={(event) =>
                            form.setData('base_fee', event.target.value)
                        }
                    />
                    <InputError message={form.errors.base_fee} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`cost-per-km-${rule.id}`}>
                        Cost per km
                    </Label>
                    <Input
                        id={`cost-per-km-${rule.id}`}
                        type="number"
                        min={0}
                        step="0.01"
                        value={form.data.cost_per_km}
                        onChange={(event) =>
                            form.setData('cost_per_km', event.target.value)
                        }
                    />
                    <InputError message={form.errors.cost_per_km} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`free-distance-${rule.id}`}>
                        Free delivery distance (km)
                    </Label>
                    <Input
                        id={`free-distance-${rule.id}`}
                        type="number"
                        min={0}
                        step="0.01"
                        placeholder="No free distance"
                        value={form.data.free_delivery_distance_km}
                        onChange={(event) =>
                            form.setData(
                                'free_delivery_distance_km',
                                event.target.value,
                            )
                        }
                    />
                    <InputError
                        message={form.errors.free_delivery_distance_km}
                    />
                </div>
                <div className="space-y-2">
                    <Label htmlFor={`min-charge-${rule.id}`}>
                        Minimum delivery charge
                    </Label>
                    <Input
                        id={`min-charge-${rule.id}`}
                        type="number"
                        min={0}
                        step="0.01"
                        value={form.data.minimum_delivery_charge}
                        onChange={(event) =>
                            form.setData(
                                'minimum_delivery_charge',
                                event.target.value,
                            )
                        }
                    />
                    <InputError
                        message={form.errors.minimum_delivery_charge}
                    />
                </div>
            </div>
            <p className="text-muted-foreground mt-4 text-xs">
                Delivery fee = max(minimum charge, base fee + cost per km ×
                distance). Free under the free-delivery distance.
            </p>
            <Button type="submit" className="mt-4" disabled={form.processing}>
                Save shipping rule
            </Button>
        </form>
    );
}

export default function PlatformDeliverySettingsIndex({
    methods,
    branchShippingRules,
}: {
    methods: DeliveryMethodRow[];
    branchShippingRules: BranchShippingRuleRow[];
}) {
    return (
        <>
            <Head title="Delivery settings" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Delivery settings"
                    description="Regular and Express methods shown at checkout."
                />
                <div className="grid gap-4">
                    {methods.map((method) => (
                        <DeliveryMethodForm key={method.id} method={method} />
                    ))}
                </div>

                <Heading
                    title="Branch shipping rules"
                    description="Dynamic per-branch delivery fee, based on distance from the branch to the customer."
                />
                <div className="grid gap-4">
                    {branchShippingRules.map((rule) => (
                        <BranchShippingRuleForm key={rule.id} rule={rule} />
                    ))}
                </div>
            </div>
        </>
    );
}

PlatformDeliverySettingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Delivery settings',
            href: '/platform/delivery-settings',
        },
    ],
};
