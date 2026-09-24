import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { ProductCard } from '@/components/storefront/product-card';
import { ProductGallery } from '@/components/storefront/product/product-gallery';
import { ProductResponsibleRetail } from '@/components/storefront/product/product-responsible-retail';
import { ProductReviews } from '@/components/storefront/product/product-reviews';
import {
    ProductSectionTabs,
    type ProductSectionTabId,
} from '@/components/storefront/product/product-section-tabs';
import { ProductShipping } from '@/components/storefront/product/product-shipping';
import { ProductSpecifications } from '@/components/storefront/product/product-specifications';
import { ProductSummary } from '@/components/storefront/product/product-summary';
import type { StorefrontProduct } from '@/types/storefront';
import type { StorefrontBranchOption } from '@/components/storefront/storefront-branch-select';
import { show as productShow } from '@/routes/products';

type ProductDetail = {
    id: string;
    name: string;
    description: string;
    category: string;
    categorySlug: string | null;
    badges: string[];
    images: string[];
    price: string;
    compareAtPrice: string;
    availability: string;
    rating: number;
    reviewCount: number;
    about: string;
    branch: string;
    stock: number;
    cardPrice: string;
    image: string;
    specifications: Array<{ label: string; value: string }>;
    shippingPoints: string[];
    responsiblePoints: string[];
    reviews: Array<{
        id: string;
        author: string;
        date: string;
        rating: number;
        body: string;
    }>;
};

type ProductPageProps = {
    product: ProductDetail;
    similarProducts: StorefrontProduct[];
    branches: StorefrontBranchOption[];
    branchId: string | null;
    backHref: string;
};

export default function StorefrontProduct({
    product,
    similarProducts,
    branches,
    branchId,
    backHref,
}: ProductPageProps) {
    const [activeTab, setActiveTab] =
        useState<ProductSectionTabId>('specifications');

    const onBranchChange = (nextBranchId: string | null) => {
        router.cancelAll();
        router.get(
            productShow.url(product.id),
            nextBranchId ? { branch: nextBranchId } : {},
            {
                preserveState: true,
                replace: true,
                only: ['product', 'branches', 'branchId'],
            },
        );
    };

    return (
        <>
            <Head title={product.name} />

            <div className="container mx-auto px-4 py-8 sm:px-6 sm:py-10">
                <Link
                    href={backHref}
                    prefetch
                    className="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-2 text-sm font-medium transition"
                >
                    <ArrowLeft className="size-4" />
                    Back to products
                </Link>

                <div className="grid min-w-0 gap-8 lg:grid-cols-2 lg:gap-12">
                    <ProductGallery
                        className="min-w-0"
                        images={product.images}
                        alt={product.name}
                    />
                    <ProductSummary
                        className="min-w-0"
                        name={product.name}
                        description={product.description}
                        badges={product.badges}
                        price={product.price}
                        compareAtPrice={product.compareAtPrice}
                        stock={product.stock}
                        rating={product.rating}
                        reviewCount={product.reviewCount}
                        about={product.about}
                        branch={product.branch}
                        branches={branches}
                        branchId={branchId}
                        onBranchChange={onBranchChange}
                    />
                </div>

                <div className="mt-12 flex min-w-0 flex-col gap-10">
                    <ProductSectionTabs
                        className="min-w-0"
                        active={activeTab}
                        onChange={setActiveTab}
                        reviewCount={product.reviews.length}
                    />

                    {activeTab === 'specifications' ? (
                        <ProductSpecifications rows={product.specifications} />
                    ) : null}

                    {activeTab === 'shipping' ? (
                        <ProductShipping points={product.shippingPoints} />
                    ) : null}

                    {activeTab === 'responsible' ? (
                        <ProductResponsibleRetail
                            points={product.responsiblePoints}
                        />
                    ) : null}

                    {activeTab === 'reviews' ? (
                        <ProductReviews
                            rating={product.rating}
                            reviewCount={product.reviewCount}
                            reviews={product.reviews}
                        />
                    ) : null}
                </div>

                {similarProducts.length > 0 ? (
                    <section className="mt-14">
                        <h2 className="text-foreground mb-6 text-2xl font-bold tracking-tight">
                            Similar Products
                        </h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                            {similarProducts.map((item) => (
                                <ProductCard key={item.id} product={item} />
                            ))}
                        </div>
                    </section>
                ) : null}
            </div>
        </>
    );
}
