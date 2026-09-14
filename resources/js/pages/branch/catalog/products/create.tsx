import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ProductForm } from '@/components/platform/product-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Branch = { id: number; name: string; slug: string };
type CategoryOption = { id: number; name: string; slug: string };
type Suggestion = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    image_url: string | null;
};

export default function BranchProductsCreate({
    branch,
    categoryOptions,
}: {
    branch: Branch;
    categoryOptions: CategoryOption[];
}) {
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
    const reuseForm = useForm({
        product_id: 0,
        selling_price: '',
        quantity: 0,
        minimum_stock: 0,
    });

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Products',
                href: ProductController.index.url(branch.slug),
            },
            {
                title: 'Add',
                href: ProductController.create.url(branch.slug),
            },
        ],
    });

    useEffect(() => {
        if (query.trim().length < 2) {
            setSuggestions([]);
            return;
        }

        const timer = window.setTimeout(() => {
            fetch(
                ProductController.suggestions.url(branch.slug, {
                    query: { q: query },
                }),
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            )
                .then((response) => response.json())
                .then((payload: { suggestions: Suggestion[] }) => {
                    setSuggestions(payload.suggestions ?? []);
                })
                .catch(() => setSuggestions([]));
        }, 300);

        return () => window.clearTimeout(timer);
    }, [branch.slug, query]);

    return (
        <>
            <Head title="Add product" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add product"
                    description="Reuse an existing catalog item or create a new global product enabled only for this branch"
                />

                <Alert>
                    <AlertTitle>Prefer reuse</AlertTitle>
                    <AlertDescription>
                        Search the global catalog first. Creating a new product
                        adds it for every branch, but only enables{' '}
                        {branch.name}.
                    </AlertDescription>
                </Alert>

                <section className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="suggest">Find existing product</Label>
                        <Input
                            id="suggest"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search by name or SKU…"
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="reuse_selling_price">
                                Selling price
                            </Label>
                            <Input
                                id="reuse_selling_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={reuseForm.data.selling_price}
                                onChange={(event) =>
                                    reuseForm.setData(
                                        'selling_price',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional"
                            />
                            <InputError
                                message={reuseForm.errors.selling_price}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reuse_quantity">
                                Initial quantity
                            </Label>
                            <Input
                                id="reuse_quantity"
                                type="number"
                                value={reuseForm.data.quantity}
                                onChange={(event) =>
                                    reuseForm.setData(
                                        'quantity',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={reuseForm.errors.quantity} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reuse_minimum_stock">
                                Minimum stock
                            </Label>
                            <Input
                                id="reuse_minimum_stock"
                                type="number"
                                min="0"
                                value={reuseForm.data.minimum_stock}
                                onChange={(event) =>
                                    reuseForm.setData(
                                        'minimum_stock',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError
                                message={reuseForm.errors.minimum_stock}
                            />
                        </div>
                    </div>

                    {suggestions.length > 0 ? (
                        <ul className="space-y-2">
                            {suggestions.map((suggestion) => (
                                <li
                                    key={suggestion.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3"
                                >
                                    <div>
                                        <div className="font-medium">
                                            {suggestion.name}
                                        </div>
                                        <div className="text-muted-foreground text-xs">
                                            {suggestion.sku ?? suggestion.slug}
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        onClick={() => {
                                            reuseForm
                                                .transform((data) => ({
                                                    ...data,
                                                    product_id: suggestion.id,
                                                    selling_price:
                                                        data.selling_price ===
                                                        ''
                                                            ? null
                                                            : data.selling_price,
                                                }))
                                                .post(
                                                    ProductController.reuse.url(
                                                        branch.slug,
                                                    ),
                                                );
                                        }}
                                        disabled={reuseForm.processing}
                                    >
                                        Use existing
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </section>

                <section className="space-y-4">
                    <h2 className="text-lg font-medium">Or create new</h2>
                    <ProductForm
                        action={ProductController.store.form(branch.slug)}
                        categoryOptions={categoryOptions}
                        cancelHref={ProductController.index.url(branch.slug)}
                        submitLabel="Create & enable here"
                        branchOpsFields
                    />
                </section>
            </div>
        </>
    );
}
