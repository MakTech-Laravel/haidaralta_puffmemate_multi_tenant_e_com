import { Link } from '@inertiajs/react';
import { AppBrandMark } from '@/components/app-brand-mark';
import { useAppBrand } from '@/hooks/use-app-brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const brand = useAppBrand();

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <AppBrandMark
                                className="mb-1"
                                sizeClassName="size-9"
                                defaultBackgroundClassName="bg-transparent"
                                iconClassName="size-9 fill-current text-[var(--foreground)] dark:text-white"
                                imageClassName="size-9 object-contain"
                            />
                            <span className="sr-only">{brand.name}</span>
                        </Link>

                        <div className="space-y-1.5 text-center">
                            <h1 className="text-foreground text-xl font-bold tracking-tight">
                                {title}
                            </h1>
                            <p className="text-muted-foreground text-center text-sm">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
