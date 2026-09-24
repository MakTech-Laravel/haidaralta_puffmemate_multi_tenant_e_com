import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { CategoryCard } from '@/components/storefront/category-card';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { HomeDealsBanner } from '@/components/storefront/home/deals-banner';
import { HomeHeroSlider } from '@/components/storefront/home/hero-slider';
import { ProductCarousel } from '@/components/storefront/home/product-carousel';
import { storefrontFeatures } from '@/lib/storefront-features';
import type {
    StorefrontHeroSlide,
    StorefrontProduct,
} from '@/types/storefront';
import { shop } from '@/routes';
import type { StorefrontCategoryItem } from '@/types/storefront';

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
    const topCategories = featuredCategories.filter(
        (category) => !category.wide,
    );
    const wideCategories = featuredCategories.filter(
        (category) => category.wide,
    );

    return (
        <>
            <Head title="Home" />

            <HomeHeroSlider slides={heroSlides} />

            <section className="container mx-auto px-4 py-14 sm:px-6 sm:py-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                            Browse the collection
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
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

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-3 [&>:last-child:nth-child(odd)]:col-span-2 lg:[&>:last-child:nth-child(odd)]:col-span-1">
                    {topCategories.map((category) => (
                        <CategoryCard key={category.id} category={category} />
                    ))}
                </div>
                {wideCategories.length > 0 ? (
                    <div className="mt-4 grid grid-cols-2 gap-4">
                        {wideCategories.map((category) => (
                            <CategoryCard
                                key={category.id}
                                category={category}
                            />
                        ))}
                    </div>
                ) : null}
            </section>
            <section className="container mx-auto px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                            Just landed
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
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
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal uppercase">
                            Hand-picked for you
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight uppercase sm:text-[40px]">
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
