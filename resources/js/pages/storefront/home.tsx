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
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal  uppercase">
                            Browse the collection
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight sm:text-[40px] uppercase">
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

                <div className="grid gap-4 sm:grid-cols-2 sm:[&>:last-child:nth-child(odd)]:col-span-2 lg:grid-cols-3 lg:[&>:last-child:nth-child(odd)]:col-span-1">
                    {topCategories.map((category) => (
                        <CategoryCard key={category.id} category={category} />
                    ))}
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2 sm:[&>:last-child:nth-child(odd)]:col-span-2">
                    {wideCategories.map((category) => (
                        <CategoryCard key={category.id} category={category} />
                    ))}
                </div>
            </section>

            <section className="mx-auto container px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal  uppercase">
                            Hand-picked for you
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight sm:text-[40px] uppercase">
                            Featured products
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

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    {storefrontFeaturedProducts.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </section>

            <section className="mx-auto container px-4 pb-14 sm:px-6 sm:pb-16">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-muted-foreground font-inter mb-1 text-sm font-normal  uppercase">
                            Just landed
                        </p>
                        <h2 className="text-foreground text-2xl font-medium tracking-tight sm:text-[40px] uppercase">
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

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    {storefrontNewArrivals.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            </section>

            <FeaturesBar features={storefrontFeatures} />
        </>
    );
}
