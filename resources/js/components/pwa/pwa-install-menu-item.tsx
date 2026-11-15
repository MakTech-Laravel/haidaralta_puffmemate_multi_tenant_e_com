import { Smartphone } from 'lucide-react';
import { usePwaInstall } from '@/components/pwa/pwa-install-provider';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
                pwa.openInstallDialog();
            }}
            data-test="pwa-install-menu"
        >
            <Smartphone className="size-4 shrink-0" aria-hidden />
            Get the app
        </button>
    );
}

export function PwaInstallHeaderButton() {
    const pwa = usePwaInstall();

    if (!pwa?.showMenuItem) {
        return null;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    className="text-foreground hover:bg-muted/70 inline-flex size-11 items-center justify-center rounded-full transition-colors"
                    aria-label="Get the app"
                    data-test="pwa-install-header"
                    onClick={() => pwa.openInstallDialog()}
                >
                    <Smartphone
                        className="size-5"
                        strokeWidth={1.75}
                        aria-hidden
                    />
                </button>
            </TooltipTrigger>
            <TooltipContent side="bottom">Get the app</TooltipContent>
        </Tooltip>
    );
}
