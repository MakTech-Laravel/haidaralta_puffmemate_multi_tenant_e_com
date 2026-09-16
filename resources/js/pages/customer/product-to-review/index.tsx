import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { ProductToReviewList } from '@/components/customer/product-to-review-list';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import { index as productToReview } from '@/routes/customer/product-to-review';

type ProductToReviewIndexProps = {
    products: ProductToReview[];
};

export default function ProductToReviewIndex({
    products,
}: ProductToReviewIndexProps) {
    const [page, setPage] = useState(1);

    return (
        <>
            <Head title="Product To Review" />

            <ProductToReviewList
                products={products}
                page={page}
                onPageChange={setPage}
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
