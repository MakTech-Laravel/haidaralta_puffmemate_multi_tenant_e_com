import {
    createContext,
    useContext,
    useEffect,
    useState,
    type ReactNode,
} from 'react';
import { Bell, Share, Smartphone, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { requestFcmToken } from '@/lib/fcm';
import {
    clearDeferredInstallPrompt,
    getDeferredInstallPrompt,
    isInAppBrowser,
    isIosSafari,
    isStandaloneDisplay,
    PWA_BANNER_DISMISSED_KEY,
    subscribeInstallPrompt,
    type BeforeInstallPromptEvent,
} from '@/lib/pwa';

export type PwaInstallContextValue = {
    isInstalled: boolean;
    canPrompt: boolean;
    isIos: boolean;
    isInAppBrowser: boolean;
    showMenuItem: boolean;
    install: () => Promise<void>;
    openInstallDialog: () => void;
};

const PwaInstallContext = createContext<PwaInstallContextValue | null>(null);

export function usePwaInstall(): PwaInstallContextValue | null {
    return useContext(PwaInstallContext);
}

type PwaInstallProviderProps = {
    children: ReactNode;
    name: string;
};

type InstallView = 'offer' | 'ios' | 'in-app' | 'unavailable';

function AppMark() {
    return (
        <img
            src="/pwa/pwa-192x192.png"
            alt=""
            className="bg-muted size-12 shrink-0 rounded-2xl"
        />
    );
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function PwaInstallProvider({
    children,
    name,
}: PwaInstallProviderProps) {
    const [isInstalled, setIsInstalled] = useState(isStandaloneDisplay);
    const [deferredPrompt, setDeferredPrompt] =
        useState<BeforeInstallPromptEvent | null>(getDeferredInstallPrompt);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [installView, setInstallView] = useState<InstallView>('offer');
    const [bannerDismissed, setBannerDismissed] = useState(() => {
        if (typeof window === 'undefined') {
            return true;
        }

        return window.localStorage.getItem(PWA_BANNER_DISMISSED_KEY) === '1';
    });
    const [alertsOn, setAlertsOn] = useState(() => {
        if (typeof window === 'undefined' || typeof Notification === 'undefined') {
            return false;
        }

        return Notification.permission === 'granted';
    });
    const [alertsBusy, setAlertsBusy] = useState(false);
    const [alertsHint, setAlertsHint] = useState<string | null>(null);

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

        window.addEventListener('appinstalled', onDisplayMode);
        const unsubscribePrompt = subscribeInstallPrompt(setDeferredPrompt);
        setDeferredPrompt(getDeferredInstallPrompt());

        return () => {
            media.removeEventListener('change', onDisplayMode);
            window.removeEventListener('appinstalled', onDisplayMode);
            unsubscribePrompt();
        };
    }, []);

    const dismissBanner = (): void => {
        window.localStorage.setItem(PWA_BANNER_DISMISSED_KEY, '1');
        setBannerDismissed(true);
    };

    const openInstallDialog = (): void => {
        setInstallView('offer');
        setDialogOpen(true);
    };

    const install = async (): Promise<void> => {
        if (inApp) {
            setInstallView('in-app');
            setDialogOpen(true);

            return;
        }

        if (isIos) {
            setInstallView('ios');
            setDialogOpen(true);

            return;
        }

        if (!deferredPrompt) {
            setInstallView('unavailable');
            setDialogOpen(true);

            return;
        }

        await deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        clearDeferredInstallPrompt();

        if (choice.outcome === 'accepted') {
            dismissBanner();
            setDialogOpen(false);
        }
    };

    const turnOnAlerts = async (): Promise<void> => {
        if (typeof Notification === 'undefined') {
            setAlertsHint('This browser does not support alerts.');

            return;
        }

        setAlertsBusy(true);
        setAlertsHint(null);

        try {
            const token = await requestFcmToken();
            const granted = Notification.permission === 'granted';
            setAlertsOn(granted);

            if (!granted) {
                setAlertsHint(
                    'Allow notifications for this site in your browser settings.',
                );

                return;
            }

            if (!token) {
                return;
            }

            await fetch('/fcm/register', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    token,
                    platform: 'web',
                    device_name: navigator.userAgent.slice(0, 255),
                }),
            });
        } catch {
            setAlertsHint('Alerts could not be turned on from this device.');
        } finally {
            setAlertsBusy(false);
        }
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
                openInstallDialog,
            }}
        >
            {children}
            {showBanner ? (
                <div className="pointer-events-none fixed inset-x-0 bottom-[calc(1rem+env(safe-area-inset-bottom))] z-40 flex justify-center px-4">
                    <div className="border-border bg-background/70 pointer-events-auto flex w-full max-w-md items-center gap-3 rounded-2xl border p-3 shadow-xl backdrop-blur-xl">
                        <AppMark />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold">
                                Get the {name} app
                            </p>
                            <p className="text-muted-foreground text-xs leading-snug">
                                Free, 5 seconds. 1-tap ordering and deals sent
                                to your phone.
                            </p>
                        </div>
                        <div className="flex shrink-0 flex-col items-center gap-1">
                            <Button
                                size="sm"
                                className="px-4"
                                onClick={openInstallDialog}
                            >
                                Install
                            </Button>
                            <button
                                type="button"
                                className="text-muted-foreground hover:text-foreground px-2 py-1 text-xs"
                                onClick={dismissBanner}
                            >
                                Not now
                            </button>
                        </div>
                    </div>
                </div>
            ) : null}
            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => {
                    setDialogOpen(open);

                    if (!open) {
                        setInstallView('offer');
                    }
                }}
            >
                <DialogContent className="gap-5 sm:max-w-md">
                    {installView === 'offer' ? (
                        <>
                            <AppMark />
                            <DialogHeader className="text-left">
                                <DialogTitle className="text-2xl">
                                    Put {name} on your home screen
                                </DialogTitle>
                                <DialogDescription>
                                    Opens like an app, in one tap. Free, no app
                                    store, takes 5 seconds.
                                </DialogDescription>
                            </DialogHeader>
                            <ul className="grid grid-cols-3 gap-2">
                                <li className="bg-muted flex flex-col items-center gap-2 rounded-2xl px-2 py-3 text-center">
                                    <Zap
                                        className="text-primary size-4"
                                        aria-hidden
                                    />
                                    <span className="text-xs font-medium">
                                        1-tap reorder
                                    </span>
                                </li>
                                <li className="bg-muted flex flex-col items-center gap-2 rounded-2xl px-2 py-3 text-center">
                                    <Bell
                                        className="text-primary size-4"
                                        aria-hidden
                                    />
                                    <span className="text-xs font-medium">
                                        Deal alerts
                                    </span>
                                </li>
                                <li className="bg-muted flex flex-col items-center gap-2 rounded-2xl px-2 py-3 text-center">
                                    <Smartphone
                                        className="text-primary size-4"
                                        aria-hidden
                                    />
                                    <span className="text-xs font-medium">
                                        Full screen
                                    </span>
                                </li>
                            </ul>
                            <Button
                                type="button"
                                className="h-11 w-full"
                                onClick={() => void install()}
                            >
                                Install {name}
                            </Button>
                            <div className="border-border flex items-center gap-3 rounded-2xl border p-3">
                                <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-full">
                                    <Bell className="size-5" aria-hidden />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold">
                                        Specials & alerts
                                    </p>
                                    <p className="text-muted-foreground text-xs leading-snug">
                                        Flash deals and new arrivals, straight
                                        to your phone.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    className="shrink-0"
                                    disabled={alertsOn || alertsBusy}
                                    onClick={() => void turnOnAlerts()}
                                >
                                    {alertsOn ? 'On' : 'Turn on'}
                                </Button>
                            </div>
                            <button
                                type="button"
                                className="text-primary inline-flex items-center gap-1.5 text-sm font-medium"
                                onClick={() => void turnOnAlerts()}
                            >
                                <Bell className="size-3.5" aria-hidden />
                                Not getting alerts? Check this device
                            </button>
                            {alertsHint ? (
                                <p className="text-muted-foreground text-xs">
                                    {alertsHint}
                                </p>
                            ) : null}
                        </>
                    ) : null}
                    {installView === 'ios' ? (
                        <>
                            <DialogHeader className="text-left">
                                <DialogTitle>Add to Home Screen</DialogTitle>
                                <DialogDescription>
                                    Safari does not show an install prompt. Use
                                    the share sheet instead.
                                </DialogDescription>
                            </DialogHeader>
                            <ol className="text-muted-foreground list-decimal space-y-2 pl-5 text-sm">
                                <li>
                                    Tap the Share icon{' '}
                                    <Share
                                        className="inline size-4 align-text-bottom"
                                        aria-hidden
                                    />{' '}
                                    at the bottom of Safari.
                                </li>
                                <li>Choose Add to Home Screen.</li>
                                <li>Tap Add.</li>
                            </ol>
                            <Button
                                type="button"
                                onClick={() => {
                                    setDialogOpen(false);
                                    dismissBanner();
                                }}
                            >
                                Got it
                            </Button>
                        </>
                    ) : null}
                    {installView === 'in-app' ? (
                        <>
                            <DialogHeader className="text-left">
                                <DialogTitle>Open in your browser</DialogTitle>
                                <DialogDescription>
                                    In-app browsers cannot add this app to your
                                    home screen. Open this page in Safari or
                                    Chrome, then install.
                                </DialogDescription>
                            </DialogHeader>
                            <Button
                                type="button"
                                onClick={() => setDialogOpen(false)}
                            >
                                Close
                            </Button>
                        </>
                    ) : null}
                    {installView === 'unavailable' ? (
                        <>
                            <DialogHeader className="text-left">
                                <DialogTitle>Install {name}</DialogTitle>
                                <DialogDescription>
                                    This browser is not offering an install
                                    prompt yet. Open the site in Chrome or
                                    Safari and try again.
                                </DialogDescription>
                            </DialogHeader>
                            <Button
                                type="button"
                                onClick={() => setDialogOpen(false)}
                            >
                                Close
                            </Button>
                        </>
                    ) : null}
                </DialogContent>
            </Dialog>
        </PwaInstallContext.Provider>
    );
}
