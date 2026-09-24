import { Head, Link, useForm } from '@inertiajs/react';
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

type PriceRangeForm = {
    id: number;
    label: string;
    slug: string;
    min_amount: string | null;
    max_amount: string | null;
    status: boolean;
};

type Props = {
    priceRange: PriceRangeForm | null;
};

export default function PlatformPriceRangeForm({ priceRange }: Props) {
    const isEdit = priceRange !== null;

    const form = useForm({
        label: priceRange?.label ?? '',
        slug: priceRange?.slug ?? '',
        min_amount: priceRange?.min_amount ?? '',
        max_amount: priceRange?.max_amount ?? '',
        status: priceRange?.status ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEdit) {
            form.put(`/platform/price-ranges/${priceRange.id}`, {
                preserveScroll: true,
            });

            return;
        }

        form.post('/platform/price-ranges');
    };

    return (
        <>
            <Head title={isEdit ? 'Edit price range' : 'Add price range'} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={isEdit ? 'Edit price range' : 'Add price range'}
                    description="Label and AUD min/max amounts for a shop sidebar preset. Leave min or max empty for open-ended bands."
                />

                <form className="space-y-6" onSubmit={submit}>
                    <section className={settingsCardClass}>
                        <div className="mb-5 space-y-1">
                            <h2 className="text-base font-semibold tracking-tight">
                                Range
                            </h2>
                            <p className="text-muted-foreground text-sm">
                                Shop visitors pick this band to filter catalog
                                prices.
                            </p>
                        </div>

                        <div className="flex flex-col gap-5">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <Label htmlFor="status">Show on shop</Label>
                                    <p className="text-muted-foreground text-sm">
                                        Turn off to hide without deleting.
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
                                <Label htmlFor="label">Label</Label>
                                <Input
                                    id="label"
                                    value={form.data.label}
                                    onChange={(event) =>
                                        form.setData('label', event.target.value)
                                    }
                                    placeholder="Under $20"
                                />
                                <InputError message={form.errors.label} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={form.data.slug}
                                    onChange={(event) =>
                                        form.setData('slug', event.target.value)
                                    }
                                    placeholder="under-20"
                                />
                                <InputError message={form.errors.slug} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="min_amount">Min amount</Label>
                                    <Input
                                        id="min_amount"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        inputMode="decimal"
                                        value={form.data.min_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'min_amount',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Optional"
                                    />
                                    <InputError
                                        message={form.errors.min_amount}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="max_amount">Max amount</Label>
                                    <Input
                                        id="max_amount"
                                        type="number"
                                        min={0}
                                        step="0.01"
                                        inputMode="decimal"
                                        value={form.data.max_amount}
                                        onChange={(event) =>
                                            form.setData(
                                                'max_amount',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Optional"
                                    />
                                    <InputError
                                        message={form.errors.max_amount}
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <div className="flex flex-wrap gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {isEdit ? 'Save range' : 'Create range'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/platform/price-ranges">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
