import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

const settingsCardClass = cn(
    'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
    'sm:p-6',
);

type CouponForm = {
    id: number;
    code: string;
    type: string;
    value: string;
    min_subtotal: string | null;
    max_discount: string | null;
    starts_at: string | null;
    ends_at: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    status: boolean;
};

export default function PlatformCouponForm({
    coupon,
}: {
    coupon: CouponForm | null;
}) {
    const isEdit = coupon !== null;
    const form = useForm({
        code: coupon?.code ?? '',
        type: coupon?.type ?? 'fixed',
        value: coupon?.value ?? '',
        min_subtotal: coupon?.min_subtotal ?? '',
        max_discount: coupon?.max_discount ?? '',
        starts_at: coupon?.starts_at ?? '',
        ends_at: coupon?.ends_at ?? '',
        usage_limit: coupon?.usage_limit ?? '',
        usage_limit_per_customer: coupon?.usage_limit_per_customer ?? '',
        status: coupon?.status ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(`/platform/coupons/${coupon.id}`, {
                preserveScroll: true,
            });

            return;
        }

        form.post('/platform/coupons');
    };

    return (
        <>
            <Head title={isEdit ? 'Edit coupon' : 'Add coupon'} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={isEdit ? 'Edit coupon' : 'Add coupon'}
                    description="Fixed or percent discounts with optional minimums and usage caps."
                />

                <form className="space-y-6" onSubmit={submit}>
                    <section className={settingsCardClass}>
                        <div className="flex flex-col gap-5">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="status">Active</Label>
                                    <p className="text-muted-foreground text-sm">
                                        Inactive codes are rejected at checkout.
                                    </p>
                                </div>
                                <Switch
                                    id="status"
                                    checked={form.data.status}
                                    onCheckedChange={(checked) =>
                                        form.setData('status', checked)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    value={form.data.code}
                                    onChange={(event) =>
                                        form.setData('code', event.target.value)
                                    }
                                />
                                <InputError message={form.errors.code} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Type</Label>
                                    <Select
                                        value={form.data.type}
                                        onValueChange={(value) =>
                                            form.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="fixed">
                                                Fixed amount
                                            </SelectItem>
                                            <SelectItem value="percent">
                                                Percentage
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.type} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="value">Value</Label>
                                    <Input
                                        id="value"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        value={form.data.value}
                                        onChange={(event) =>
                                            form.setData(
                                                'value',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.value} />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="min_subtotal">
                                        Minimum subtotal
                                    </Label>
                                    <Input
                                        id="min_subtotal"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        value={form.data.min_subtotal}
                                        onChange={(event) =>
                                            form.setData(
                                                'min_subtotal',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.min_subtotal}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="max_discount">
                                        Max discount
                                    </Label>
                                    <Input
                                        id="max_discount"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        value={form.data.max_discount}
                                        onChange={(event) =>
                                            form.setData(
                                                'max_discount',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.max_discount}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="starts_at">Starts</Label>
                                    <Input
                                        id="starts_at"
                                        type="date"
                                        value={form.data.starts_at}
                                        onChange={(event) =>
                                            form.setData(
                                                'starts_at',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.starts_at}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ends_at">Ends</Label>
                                    <Input
                                        id="ends_at"
                                        type="date"
                                        value={form.data.ends_at}
                                        onChange={(event) =>
                                            form.setData(
                                                'ends_at',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.ends_at} />
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="usage_limit">
                                        Usage limit
                                    </Label>
                                    <Input
                                        id="usage_limit"
                                        type="number"
                                        min={1}
                                        value={form.data.usage_limit}
                                        onChange={(event) =>
                                            form.setData(
                                                'usage_limit',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.usage_limit}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="usage_limit_per_customer">
                                        Per customer
                                    </Label>
                                    <Input
                                        id="usage_limit_per_customer"
                                        type="number"
                                        min={1}
                                        value={
                                            form.data.usage_limit_per_customer
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'usage_limit_per_customer',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={
                                            form.errors.usage_limit_per_customer
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    </section>
                    <div className="flex flex-wrap gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {isEdit ? 'Save coupon' : 'Create coupon'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/platform/coupons">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

PlatformCouponForm.layout = {
    breadcrumbs: [
        { title: 'Coupons', href: '/platform/coupons' },
        { title: 'Form', href: '/platform/coupons/create' },
    ],
};
