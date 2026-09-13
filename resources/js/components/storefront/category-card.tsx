import { Link } from '@inertiajs/react';
import type { StorefrontCategoryItem } from '@/lib/storefront-demo';
import { cn } from '@/lib/utils';

type CategoryCardProps = {
    category: StorefrontCategoryItem;
    className?: string;
};

export function CategoryCard({ category, className }: CategoryCardProps) {
    return (
        <Link
            href={category.href}
            prefetch
            className={cn(
                'group ring-ring relative block aspect-4/3 overflow-hidden rounded-xl focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                className,
            )}
        >
            <div
                className="bg-foreground absolute inset-0 bg-cover bg-center transition duration-500 group-hover:scale-105"
                style={{ backgroundImage: `url(${category.image})` }}
                aria-hidden
            />
            <div
                className="absolute inset-0 bg-linear-to-t from-foreground/75 via-foreground/5 to-transparent"
                aria-hidden
            />
            <div className="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                <h3 className="text-primary-foreground text-lg font-semibold sm:text-xl">
                    {category.name}
                </h3>
                <p className="text-primary-foreground/80 mt-1 text-sm">
                    {category.description}
                </p>
            </div>
        </Link>
    );
}
