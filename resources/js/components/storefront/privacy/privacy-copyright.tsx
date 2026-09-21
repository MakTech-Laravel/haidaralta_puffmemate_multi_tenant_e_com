import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';

type PrivacyCopyrightProps = {
    className?: string;
};

export function PrivacyCopyright({ className }: PrivacyCopyrightProps) {
    const brand = useAppBrand();

    return (
        <section className={cn('bg-background pb-14 sm:pb-16', className)}>
            <div className="container px-4 sm:px-6">
                <p className="text-muted-foreground text-center text-xs sm:text-sm">
                    © {new Date().getFullYear()} {brand.name}. All rights
                    reserved.
                </p>
            </div>
        </section>
    );
}
