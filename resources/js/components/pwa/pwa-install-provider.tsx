import {
    createContext,
    useContext,
    useEffect,
    useState,
    type ReactNode,
} from 'react';
import { Download, Share, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    isInAppBrowser,
    isIosSafari,
    isStandaloneDisplay,
    PWA_BANNER_DISMISSED_KEY,
    type BeforeInstallPromptEvent,
} from '@/lib/pwa';

export type PwaInstallContextValue = {
    isInstalled: boolean;
    canPrompt: boolean;
    isIos: boolean;
    isInAppBrowser: boolean;
    showMenuItem: boolean;
    install: () => Promise<void>;
};

const PwaInstallContext = createContext<PwaInstallContextValue | null>(null);

export function usePwaInstall(): PwaInstallContextValue | null {
    return useContext(PwaInstallContext);
}

type PwaInstallProviderProps = {
    children: ReactNode;
    name: string;
};

export function PwaInstallProvider({
    children,
    name,
}: PwaInstallProviderProps) {
    const [isInstalled, setIsInstalled] = useState(isStandaloneDisplay);
    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(null);
    const [iosDialogOpen, setIosDialogOpen] = useState(false);
    const [inAppDialogOpen, setInAppDialogOpen] = useState(false);
    const [bannerDismissed, setBannerDismissed] = useState(() => {
        if (typeof window === 'undefined') {
            return true;
        }

        return window.localStorage.getItem(PWA_BANNER_DISMISSED_KEY) === '1';
    });

    const isIos = isIosSafari();
    const inApp = isInAppBrowser();
    const canPrompt = deferredPrompt !== null;
    const showMenuItem = !isInstalled && (canPrompt || isIos || inApp);
    const showBanner = showMenuItem && !bannerDismissed;

    useEffect(() => {
        const onDisplayMode = (): void => {
            setIsInstalled(isStandaloneDisplay());
        };

        const media = window.matchMedia('(display-mode: standalone)');
        media.addEventListener('change', onDisplayMode);

        const onBeforeInstall = (event: Event): void => {
            event.preventDefault();
            setDeferredPrompt(event as BeforeInstallPromptEvent);
        };

        window.addEventListener('beforeinstallprompt', onBeforeInstall);
        window.addEventListener('appinstalled', onDisplayMode);

        return () => {
            media.removeEventListener('change', onDisplayMode);
            window.removeEventListener('beforeinstallprompt', onBeforeInstall);
            window.removeEventListener('appinstalled', onDisplayMode);
        };
    }, []);

    const dismissBanner = (): void => {
        window.localStorage.setItem(PWA_BANNER_DISMISSED_KEY, '1');
        setBannerDismissed(true);
    };

    const install = async (): Promise<void> => {
        if (inApp) {
            setInAppDialogOpen(true);

            return;
        }

        if (isIos) {
            setIosDialogOpen(true);

            return;
        }

        if (!deferredPrompt) {
            return;
        }

        await deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        setDeferredPrompt(null);
        dismissBanner();
    };

    return (
        <PwaInstallContext.Provider
            value={{
                isInstalled,
                canPrompt,
                isIos,
                isInAppBrowser: inApp,
                showMenuItem,
                install,
            }}
        >
            {children}
            {showBanner ? (
                <div className="pointer-events-none fixed inset-x-0 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] z-40 flex justify-center px-4 xl:bottom-6">
                    <div className="pointer-events-auto border-border bg-background flex w-full max-w-lg items-center gap-3 rounded-xl border p-3 shadow-lg">
                        <Download className="text-primary size-5 shrink-0" aria-hidden />
                        <p className="min-w-0 flex-1 text-sm font-medium">
                            Install {name} on your home screen
                        </p>
                        <Button size="sm" onClick={() => void install()}>
                            Install
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="ghost"
                            aria-label="Dismiss install banner"
                            onClick={dismissBanner}
                        >
                            <X />
                        </Button>
                    </div>
                </div>
            ) : null}
            <Dialog open={iosDialogOpen} onOpenChange={setIosDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add to Home Screen</DialogTitle>
                        <DialogDescription>
                            Safari does not show an install prompt. Use the share
                            sheet instead.
                        </DialogDescription>
                    </DialogHeader>
                    <ol className="text-muted-foreground list-decimal space-y-2 pl-5 text-sm">
                        <li>
                            Tap the Share icon{' '}
                            <Share className="inline size-4 align-text-bottom" aria-hidden />
                            {' '}at the bottom of Safari.
                        </li>
                        <li>Choose Add to Home Screen.</li>
                        <li>Tap Add.</li>
                    </ol>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() => {
                                setIosDialogOpen(false);
                                dismissBanner();
                            }}
                        >
                            Got it
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={inAppDialogOpen} onOpenChange={setInAppDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Open in your browser</DialogTitle>
                        <DialogDescription>
                            In-app browsers cannot add this app to your home
                            screen. Open this page in Safari or Chrome, then
                            install.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() => setInAppDialogOpen(false)}
                        >
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PwaInstallContext.Provider>
    );
}
