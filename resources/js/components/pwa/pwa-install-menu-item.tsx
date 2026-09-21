import { Download } from 'lucide-react';
import { usePwaInstall } from '@/components/pwa/pwa-install-provider';
import { cn } from '@/lib/utils';

type PwaInstallMenuItemProps = {
    className?: string;
    onInstalledNavigate?: () => void;
};

export function PwaInstallMenuItem({
    className,
    onInstalledNavigate,
}: PwaInstallMenuItemProps) {
    const pwa = usePwaInstall();

    if (!pwa?.showMenuItem) {
        return null;
    }

    return (
        <button
            type="button"
            className={cn(
                'flex w-full cursor-pointer items-center gap-2 text-left',
                className,
            )}
            onClick={() => {
                onInstalledNavigate?.();
                void pwa.install();
            }}
            data-test="pwa-install-menu"
        >
            <Download className="size-4 shrink-0" aria-hidden />
            Install app
        </button>
    );
}
