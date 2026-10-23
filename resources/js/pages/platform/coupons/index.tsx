import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';

type CouponRow = {
    id: number;
    code: string;
    type: string;
    type_label: string;
    value: string;
    min_subtotal: string | null;
    max_discount: string | null;
    starts_at: string | null;
    ends_at: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    status: boolean;
    redemptions: number;
};

export default function PlatformCouponsIndex({
    coupons,
}: {
    coupons: CouponRow[];
}) {
    return (
        <>
            <Head title="Coupons" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Coupons"
                        description="Marketing coupons for storefront checkout. Discount applies to cart product total only."
                    />
                    <Button asChild>
                        <Link href="/platform/coupons/create">
                            <Plus className="size-4" />
                            Add coupon
                        </Link>
                    </Button>
                </div>

                {coupons.length === 0 ? (
                    <div className="border-border/80 bg-card rounded-2xl border p-8 text-center shadow-sm">
                        <p className="text-muted-foreground text-sm">
                            No coupons yet.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {coupons.map((coupon) => (
                            <div
                                key={coupon.id}
                                className="border-border/80 bg-card flex flex-col gap-4 rounded-2xl border p-4 shadow-sm sm:flex-row sm:items-center"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-foreground font-semibold">
                                            {coupon.code}
                                        </p>
                                        <Badge variant="secondary">
                                            {coupon.type_label}
                                        </Badge>
                                        {!coupon.status ? (
                                            <Badge variant="outline">Off</Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Value {coupon.value}
                                        {coupon.min_subtotal
                                            ? ` · min $${coupon.min_subtotal}`
                                            : ''}
                                        {` · ${coupon.redemptions} used`}
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <Switch
                                        checked={coupon.status}
                                        onCheckedChange={(checked) =>
                                            router.put(
                                                `/platform/coupons/${coupon.id}`,
                                                { ...coupon, status: checked },
                                                { preserveScroll: true },
                                            )
                                        }
                                        aria-label={`Toggle ${coupon.code}`}
                                    />
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        asChild
                                    >
                                        <Link
                                            href={`/platform/coupons/${coupon.id}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Remove “${coupon.code}”?`,
                                                )
                                            ) {
                                                router.delete(
                                                    `/platform/coupons/${coupon.id}`,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

PlatformCouponsIndex.layout = {
    breadcrumbs: [{ title: 'Coupons', href: '/platform/coupons' }],
};
