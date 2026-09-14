import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import Heading from '@/components/heading';
import {
    ProductForm,
    type ProductSpecificationRow,
    type ProductTagValue,
} from '@/components/platform/product-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type CategoryOption = {
    id: number;
    name: string;
};

type GalleryImage = {
    id: number;
    path: string;
    url: string;
    sort_order: number;
    mime_type?: string;
};

type Product = {
    id: number;
    category_ids: number[];
    name: string;
    slug: string;
    sku: string | null;
    brand: string | null;
    description: string | null;
    tags: ProductTagValue[];
    specifications: ProductSpecificationRow[];
    image: string | null;
    image_url: string | null;
    gallery: GalleryImage[];
    color_code?: import('@/lib/color-picker').ColorPickerValue | null;
    status: boolean;
};

export default function PlatformProductsEdit({
    product,
    categoryOptions,
}: {
    product: Product;
    categoryOptions: CategoryOption[];
}) {
    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <Heading
                    title={`Edit ${product.name}`}
                    description="Update catalog details, primary image, and gallery"
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
                    action={ProductController.update.form(product.slug)}
                    categoryOptions={categoryOptions}
                    isEdit
                    enableSuggestions
                    excludeProductId={product.id}
                    defaults={{
                        category_ids: product.category_ids,
                        name: product.name,
                        slug: product.slug,
                        sku: product.sku,
                        brand: product.brand,
                        description: product.description,
                        tags: product.tags,
                        specifications: product.specifications,
                        image: product.image,
                        image_url: product.image_url,
                        gallery: product.gallery,
                        color_code: product.color_code,
                        status: product.status,
                    }}
                    cancelHref={ProductController.show.url(product.slug)}
                />
            </div>
        </>
    );
}

PlatformProductsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Edit', href: '#' },
    ],
};
