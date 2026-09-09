import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { usePrivateRealtimeToast } from '@/hooks/use-private-realtime-toast';

type AppToasterProps = {
    authUserId?: number;
};

/**
 * App-level toast host. Keeps flash + realtime listeners out of the shadcn UI wrapper.
 */
export function AppToaster({ authUserId }: AppToasterProps) {
    useFlashToast();
    usePrivateRealtimeToast(authUserId);

    return (
        <Toaster
            position="top-right"
            richColors
            closeButton
            expand={true}
            duration={6000}
            className="toaster group z-[9999]"
        />
    );
}
