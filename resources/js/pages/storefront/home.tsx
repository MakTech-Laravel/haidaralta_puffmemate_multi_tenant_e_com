import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { CategoryCarousel } from '@/components/storefront/home/category-carousel';
import { HomeDealsBanner } from '@/components/storefront/home/deals-banner';
import { HomeHeroSlider } from '@/components/storefront/home/hero-slider';
import { ProductCarousel } from '@/components/storefront/home/product-carousel';
import { storefrontFeatures } from '@/lib/storefront-features';
import { shop } from '@/routes';
import type {
    StorefrontCategoryItem,
    StorefrontHeroSlide,
    StorefrontProduct,
} from '@/types/storefront';

type HomePageProps = {
    heroSlides: StorefrontHeroSlide[];
    featuredCategories: StorefrontCategoryItem[];
    bestSellingProducts: StorefrontProduct[];
    newArrivalProducts: StorefrontProduct[];
};

export default function StorefrontHome({
    heroSlides,
    featuredCategories,
    bestSellingProducts,
    newArrivalProducts,
}: HomePageProps) {
    return (
        <>
            <Head title="Home" />

            <HomeHeroSlider slides={heroSlides} />

            <section className="container mx-auto px-4 py-14 sm:px-6 sm:py-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-xs font-semibold tracking-[0.12em] uppercase">
                            Browse the collection
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-4xl">
                            Shop by category
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-primary hover:text-foreground inline-flex items-center gap-1 text-base font-semibold transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <CategoryCarousel categories={featuredCategories} />
            </section>
            <section className="container mx-auto px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-xs font-semibold tracking-[0.12em] uppercase">
                            Just landed
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-4xl">
                            New arrivals
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-primary hover:text-foreground inline-flex items-center gap-1 text-base font-semibold transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <ProductCarousel products={newArrivalProducts} />
            </section>

            <section className="container mx-auto px-4 pb-14 sm:px-6 sm:pb-16">
                <HomeDealsBanner />
            </section>

            <section className="container mx-auto px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-xs font-semibold tracking-[0.12em] uppercase">
                            Hand-picked for you
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-4xl">
                            Best selling products
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-primary hover:text-foreground inline-flex items-center gap-1 text-base font-semibold transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <ProductCarousel products={bestSellingProducts} />
            </section>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
