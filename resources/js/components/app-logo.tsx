import { AppBrandMark } from '@/components/app-brand-mark';
import { useAppBrand } from '@/hooks/use-app-brand';

export default function AppLogo() {
    const brand = useAppBrand();

    return (
        <>
            <AppBrandMark />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {brand.name}
                </span>
            </div>
        </>
    );
}
