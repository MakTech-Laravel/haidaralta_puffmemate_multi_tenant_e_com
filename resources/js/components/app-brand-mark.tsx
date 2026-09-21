import type { ImgHTMLAttributes } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useAppBrand } from '@/hooks/use-app-brand';
import { cn } from '@/lib/utils';

type AppBrandMarkProps = {
    className?: string;
    /** Wrapper size; default matches sidebar (`size-8`). */
    sizeClassName?: string;
    /** Background used only for the default (non-uploaded) mark. */
    defaultBackgroundClassName?: string;
    /** Classes for the default SVG icon. */
    iconClassName?: string;
    /** Classes for the uploaded logo image. */
    imageClassName?: string;
    alt?: string;
} & Pick<ImgHTMLAttributes<HTMLImageElement>, 'loading'>;

/**
 * App logo mark: uploaded logos render without a background;
 * the default mark keeps a brand-colored tile behind the icon.
 */
export function AppBrandMark({
    className,
    sizeClassName = 'size-8',
    defaultBackgroundClassName = 'bg-sidebar-primary text-sidebar-primary-foreground',
    iconClassName = 'size-5 fill-current text-white dark:text-black',
    imageClassName = 'size-full object-contain',
    alt = '',
    loading,
}: AppBrandMarkProps) {
    const brand = useAppBrand();

    if (brand.hasCustomLogo) {
        return (
            <div
                className={cn(
                    'flex aspect-square items-center justify-center overflow-hidden',
                    sizeClassName,
                    className,
                )}
            >
                <img
                    src={brand.logoUrl}
                    alt={alt}
                    loading={loading}
                    className={cn(imageClassName)}
                />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'flex aspect-square items-center justify-center overflow-hidden rounded-md',
                sizeClassName,
                defaultBackgroundClassName,
                className,
            )}
        >
            <AppLogoIcon className={iconClassName} />
        </div>
    );
}
