import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import { ProductCard } from '@/components/storefront/product-card';
import { ProductGallery } from '@/components/storefront/product-gallery';
import { ProductResponsibleRetail } from '@/components/storefront/product-responsible-retail';
import { ProductReviews } from '@/components/storefront/product-reviews';
import {
    ProductSectionTabs,
    type ProductSectionTabId,
} from '@/components/storefront/product-section-tabs';
import { ProductShipping } from '@/components/storefront/product-shipping';
import { ProductSpecifications } from '@/components/storefront/product-specifications';
import { ProductSummary } from '@/components/storefront/product-summary';
import type { StorefrontProduct } from '@/lib/storefront-demo';

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
    backHref: string;
};

function scrollToSection(tab: ProductSectionTabId): void {
    const element = document.getElementById(`product-section-${tab}`);

    if (!element) {
        return;
    }

    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

export default function StorefrontProduct({
    product,
    similarProducts,
    backHref,
}: ProductPageProps) {
    const [activeTab, setActiveTab] =
        useState<ProductSectionTabId>('specifications');

    const handleTabChange = (tab: ProductSectionTabId) => {
        setActiveTab(tab);
        scrollToSection(tab);
    };

    return (
        <>
            <Head title={product.name} />

            <div className="mx-auto container px-4 py-8 sm:px-6 sm:py-10">
                <Link
                    href={backHref}
                    prefetch
                    className="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-2 text-sm font-medium transition"
                >
                    <ArrowLeft className="size-4" />
                    Back to products
                </Link>

                <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
                    <ProductGallery images={product.images} alt={product.name} />
                    <ProductSummary
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
                    />
                </div>

                <div className="mt-12 flex flex-col gap-10">
                    <ProductSectionTabs
                        active={activeTab}
                        onChange={handleTabChange}
                        reviewCount={product.reviews.length}
                    />

                    <div
                        id="product-section-specifications"
                        className="scroll-mt-36"
                    >
                        <ProductSpecifications rows={product.specifications} />
                    </div>

                    <div id="product-section-shipping" className="scroll-mt-36">
                        <ProductShipping points={product.shippingPoints} />
                    </div>

                    <div
                        id="product-section-responsible"
                        className="scroll-mt-36"
                    >
                        <ProductResponsibleRetail
                            points={product.responsiblePoints}
                        />
                    </div>

                    <div id="product-section-reviews" className="scroll-mt-36">
                        <ProductReviews
                            rating={product.rating}
                            reviewCount={product.reviewCount}
                            reviews={product.reviews}
                        />
                    </div>
                </div>

                {similarProducts.length > 0 ? (
                    <section className="mt-14">
                        <h2 className="text-foreground mb-6 text-2xl font-bold tracking-tight">
                            Similar Products
                        </h2>
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
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
