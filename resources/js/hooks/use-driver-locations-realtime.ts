import { useEffect, useRef, useState } from 'react';
import { getEcho, getEchoGeneration } from '@/echo';

const LOCATION_EVENT = '.driver.location-updated';

export type DriverLocationEvent = {
    driver_id: number;
    branch_ids: number[];
    lat: number;
    lng: number;
    ts: number;
    heading: number | null;
    speed: number | null;
    accuracy: number | null;
    fulfillment_id: number | null;
    /** Only an approximate position (a computer without GPS). */
    coarse: boolean;
};

/**
 * Listens for live driver positions on the given private channels (one per branch map, or the
 * platform channel). Movement is applied in the page state; nothing here hits the server.
 */
export function useDriverLocationsRealtime(
    channels: string[],
    onUpdate: (event: DriverLocationEvent) => void,
): void {
    const handler = useRef(onUpdate);
    const channelKey = channels.join(',');
    const [generation, setGeneration] = useState(() => getEchoGeneration());

    handler.current = onUpdate;

    useEffect(() => {
        const onReconnected = () => setGeneration(getEchoGeneration());

        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, []);

    useEffect(() => {
        const echo = getEcho();

        if (!echo || channelKey === '') {
            return;
        }

        const listener = (event: DriverLocationEvent) => handler.current(event);
        const subscribed = channelKey.split(',').map((name) => {
            const channel = echo.private(name);

            channel.listen(LOCATION_EVENT, listener);

            return channel;
        });

        return () => {
            subscribed.forEach((channel) => {
                try {
                    channel.stopListening(LOCATION_EVENT, listener);
                } catch {
                    // Echo may already be torn down during reconnect.
                }
            });
        };
    }, [channelKey, generation]);
}
