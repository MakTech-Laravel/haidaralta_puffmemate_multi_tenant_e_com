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
    /** When set, prefer this URL over the application brand logo. */
    logoUrl?: string | null;
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
    logoUrl,
    alt = '',
    loading,
}: AppBrandMarkProps) {
    const brand = useAppBrand();
    const resolvedLogoUrl = logoUrl || brand.logoUrl;
    const showImage = Boolean(logoUrl) || (!logoUrl && brand.hasCustomLogo);

    if (showImage && resolvedLogoUrl) {
        return (
            <div
                className={cn(
                    'flex aspect-square items-center justify-center overflow-hidden rounded-md',
                    sizeClassName,
                    className,
                )}
            >
                <img
                    src={resolvedLogoUrl}
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
