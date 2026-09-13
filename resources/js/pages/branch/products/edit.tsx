import { Head, setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import Heading from '@/components/heading';
import { ProductForm } from '@/components/platform/product-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { ColorPickerValue } from '@/lib/color-picker';

type Branch = { id: number; name: string; slug: string };

type CategoryOption = {
    id: number;
    name: string;
};

type Product = {
    id: number;
    category_ids: number[];
    name: string;
    slug: string;
    sku: string | null;
    description: string | null;
    image: string | null;
    image_url: string | null;
    gallery: GalleryImage[];
    color_code?: ColorPickerValue | null;
    status: boolean;
};

type GalleryImage = {
    id: number;
    path: string;
    url: string;
    sort_order: number;
    mime_type?: string;
};

export default function BranchProductsEdit({
    branch,
    product,
    categoryOptions,
}: {
    branch: Branch;
    product: Product;
    categoryOptions: CategoryOption[];
}) {
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
                title: product.name,
                href: ProductController.show.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
            {
                title: 'Edit',
                href: ProductController.edit.url({
                    branch: branch.slug,
                    product: product.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <Heading
                    title={`Edit ${product.name}`}
                    description="Update global catalog details for this product"
                />

                {!product.status ? (
                    <Alert>
                        <AlertTitle>Product inactive</AlertTitle>
                        <AlertDescription>
                            Inactive products are hidden from storefront
                            browsing.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <ProductForm
                    action={ProductController.update.form({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                    categoryOptions={categoryOptions}
                    isEdit
                    enableSuggestions
                    suggestionsUrl={ProductController.suggestions.url(
                        branch.slug,
                    )}
                    suggestionShowHref={(slug) =>
                        ProductController.show.url({
                            branch: branch.slug,
                            product: slug,
                        })
                    }
                    excludeProductId={product.id}
                    defaults={{
                        category_ids: product.category_ids,
                        name: product.name,
                        slug: product.slug,
                        sku: product.sku,
                        description: product.description,
                        image: product.image,
                        image_url: product.image_url,
                        gallery: product.gallery,
                        color_code: product.color_code,
                        status: product.status,
                    }}
                    cancelHref={ProductController.show.url({
                        branch: branch.slug,
                        product: product.slug,
                    })}
                />
            </div>
        </>
    );
}
