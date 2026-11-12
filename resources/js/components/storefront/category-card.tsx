import { Link } from '@inertiajs/react';
import type { StorefrontCategoryItem } from '@/types/storefront';
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
                'group ring-ring relative block aspect-4/3 w-full overflow-hidden rounded-2xl focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                className,
            )}
        >
            <div
                className="bg-ink-surface absolute inset-0 bg-cover bg-center transition duration-500 group-hover:scale-105"
                style={{ backgroundImage: `url(${category.image})` }}
                aria-hidden
            />
            <div
                className="from-ink-surface/80 via-ink-surface/10 absolute inset-0 bg-linear-to-t to-transparent"
                aria-hidden
            />
            <div className="absolute inset-x-0 bottom-0 p-3.5 sm:p-5 lg:p-4">
                <h3 className="text-ink-surface-foreground line-clamp-2 text-base font-semibold tracking-tight sm:text-xl lg:text-lg xl:text-xl">
                    {category.name}
                </h3>
                <p className="text-ink-surface-foreground/85 font-dm-sans mt-1 line-clamp-2 text-xs font-normal">
                    {category.description}
                </p>
            </div>
        </Link>
    );
}
