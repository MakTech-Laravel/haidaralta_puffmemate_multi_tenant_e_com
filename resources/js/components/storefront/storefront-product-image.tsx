import { useState } from 'react';
import { cn } from '@/lib/utils';

const FALLBACK_IMAGE = '/images/storefront/logo.png';

type StorefrontProductImageProps = {
    src?: string | null;
    alt: string;
    className?: string;
};

export function StorefrontProductImage({
    src,
    alt,
    className,
}: StorefrontProductImageProps) {
    const [failed, setFailed] = useState(false);
    const imageSrc = !failed && src ? src : FALLBACK_IMAGE;

    return (
        <img
            src={imageSrc}
            alt={alt}
            className={cn('size-full object-cover', className)}
            onError={() => setFailed(true)}
        />
    );
}
