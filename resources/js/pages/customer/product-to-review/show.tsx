import { Head } from '@inertiajs/react';
import type { ProductToReview } from '@/components/customer/product-to-review-types';
import { WriteProductReviewForm } from '@/components/customer/write-product-review-form';
import {
    index as productToReviewIndex,
    show as productToReviewShow,
} from '@/routes/customer/product-to-review';

type ProductToReviewShowProps = {
    product: ProductToReview;
};

export default function ProductToReviewShow({
    product,
}: ProductToReviewShowProps) {
    return (
        <>
            <Head title={`Review ${product.name}`} />

            <WriteProductReviewForm
                itemId={product.id}
                productName={product.name}
            />
        </>
    );
}

ProductToReviewShow.layout = (props: ProductToReviewShowProps) => ({
    breadcrumbs: [
        {
            title: 'Product To Review',
            href: productToReviewIndex.url(),
        },
        {
            title: props.product.name,
            href: productToReviewShow.url(props.product.id),
        },
    ],
});
