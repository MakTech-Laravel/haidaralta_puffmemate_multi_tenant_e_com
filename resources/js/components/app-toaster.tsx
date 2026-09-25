import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { usePrivateRealtimeToast } from '@/hooks/use-private-realtime-toast';
import { useRegisterFcmToken } from '@/hooks/use-register-fcm-token';

type AppToasterProps = {
    authUserId?: number;
};

/**
 * App-level toast host. Keeps flash + realtime listeners out of the shadcn UI wrapper.
 */
export function AppToaster({ authUserId }: AppToasterProps) {
    useFlashToast();
    usePrivateRealtimeToast(authUserId);
    useRegisterFcmToken(authUserId);

    return (
        <Toaster
            position="top-right"
            richColors
            closeButton
            expand={true}
            visibleToasts={4}
            duration={6000}
            className="toaster group z-[9999]"
        />
    );
}
