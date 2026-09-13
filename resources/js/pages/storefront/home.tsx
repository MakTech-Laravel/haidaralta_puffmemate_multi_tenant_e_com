import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { CategoryCard } from '@/components/storefront/category-card';
import { FeaturesBar } from '@/components/storefront/features-bar';
import { HeroSlider } from '@/components/storefront/hero-slider';
import { ProductCard } from '@/components/storefront/product-card';
import {
    storefrontCategoryItems,
    storefrontFeaturedProducts,
    storefrontFeatures,
    storefrontHeroSlides,
    storefrontNewArrivals,
} from '@/lib/storefront-demo';
import { shop } from '@/routes';

export default function StorefrontHome() {
    const topCategories = storefrontCategoryItems.filter(
        (category) => !category.wide,
    );
    const wideCategories = storefrontCategoryItems.filter(
        (category) => category.wide,
    );

    return (
        <>
            <Head title="Home" />

            <HeroSlider slides={storefrontHeroSlides} />

            <section className="mx-auto container px-4 py-14 sm:px-6 sm:py-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-xs font-semibold tracking-[0.18em] uppercase">
                            Browse the collection
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-3xl">
                            Shop by category
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm font-medium transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {topCategories.map((category) => (
                        <CategoryCard key={category.id} category={category} />
                    ))}
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    {wideCategories.map((category) => (
                        <CategoryCard key={category.id} category={category} />
                    ))}
                </div>
            </section>

            <section className="mx-auto container px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.18em] uppercase">
                            Hand-picked for you
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-3xl">
                            Featured products
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm font-medium transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {storefrontFeaturedProducts.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </section>

            <section className="mx-auto container px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground mb-1 text-xs font-semibold tracking-[0.18em] uppercase">
                            Just landed
                        </p>
                        <h2 className="text-foreground text-2xl font-semibold tracking-tight sm:text-3xl">
                            New arrivals
                        </h2>
                    </div>
                    <Link
                        href={shop()}
                        prefetch
                        className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm font-medium transition"
                    >
                        View All
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    {storefrontNewArrivals.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </section>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
