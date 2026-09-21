import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { AppBrandMark } from '@/components/app-brand-mark';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAppBrand } from '@/hooks/use-app-brand';
import { home } from '@/routes';

export default function AuthCardLayout({
    children,
    title,
    description,
}: PropsWithChildren<{
    name?: string;
    title?: string;
    description?: string;
}>) {
    const brand = useAppBrand();

    return (
        <div className="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="flex w-full max-w-md flex-col gap-6">
                <Link
                    href={home()}
                    className="flex items-center gap-2 self-center font-medium"
                >
                    <AppBrandMark
                        sizeClassName="size-9"
                        defaultBackgroundClassName="bg-transparent"
                        iconClassName="size-9 fill-current text-black dark:text-white"
                        imageClassName="size-9 object-contain"
                    />
                    <span className="sr-only">{brand.name}</span>
                </Link>

                <div className="flex flex-col gap-6">
                    <Card className="rounded-xl">
                        <CardHeader className="px-10 pt-8 pb-0 text-center">
                            <CardTitle className="text-xl">{title}</CardTitle>
                            <CardDescription>{description}</CardDescription>
                        </CardHeader>
                        <CardContent className="px-10 py-8">
                            {children}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
