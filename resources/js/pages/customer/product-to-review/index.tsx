import { Head, router } from '@inertiajs/react';
import { ProductToReviewList } from '@/components/customer/product-to-review-list';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import { index as productToReview } from '@/routes/customer/product-to-review';

type ProductToReviewIndexProps = {
    products: ProductToReview[];
    meta: {
        currentPage: number;
        lastPage: number;
    };
};

export default function ProductToReviewIndex({
    products,
    meta,
}: ProductToReviewIndexProps) {
    return (
        <>
            <Head title="Product To Review" />

            <ProductToReviewList
                products={products}
                currentPage={meta.currentPage}
                lastPage={meta.lastPage}
                onPageChange={(page) =>
                    router.get(
                        productToReview.url(),
                        { page },
                        { preserveState: true, preserveScroll: true },
                    )
                }
            />
        </>
    );
}

ProductToReviewIndex.layout = {
    breadcrumbs: [
        {
            title: 'Product To Review',
            href: productToReview.url(),
        },
    ],
};
