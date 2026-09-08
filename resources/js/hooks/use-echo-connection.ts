import { useCallback, useEffect, useState } from 'react';
import {
    getEcho,
    getEchoGeneration,
    getReverbClientConfig,
    reconnectEcho,
} from '@/echo';
import type { ConnectionStatus } from '@/realtime/types';

function formatConnectionError(payload: unknown): string {
    if (payload == null) {
        return 'Unknown connection error';
    }

    if (typeof payload === 'string') {
        return payload;
    }

    if (typeof payload !== 'object') {
        return String(payload);
    }

    const record = payload as Record<string, unknown>;
    const nested =
        record.error && typeof record.error === 'object'
            ? (record.error as Record<string, unknown>)
            : record;
    const data =
        typeof nested.data === 'string'
            ? (() => {
                  try {
                      return JSON.parse(nested.data) as Record<
                          string,
                          unknown
                      >;
                  } catch {
                      return { message: nested.data };
                  }
              })()
            : typeof nested.data === 'object' && nested.data !== null
              ? (nested.data as Record<string, unknown>)
              : nested;

    const code = data.code ?? nested.code ?? record.code;
    const message =
        data.message ?? nested.message ?? record.message ?? 'Connection error';

    return code != null ? `${message} (code ${code})` : String(message);
}

function readPusherState(echo: ReturnType<typeof getEcho>): ConnectionStatus {
    if (!echo) {
        return getReverbClientConfig().enabled ? 'idle' : 'disabled';
    }

    const connector = echo.connector as {
        pusher?: { connection?: { state?: string } };
    };
    const state = connector.pusher?.connection?.state;

    switch (state) {
        case 'connecting':
            return 'connecting';
        case 'connected':
            return 'connected';
        case 'unavailable':
            return 'unavailable';
        case 'failed':
            return 'failed';
        case 'disconnected':
            return 'disconnected';
        default:
            return 'idle';
    }
}

export function useEchoConnection() {
    const config = getReverbClientConfig();
    const [status, setStatus] = useState<ConnectionStatus>(() =>
        config.enabled ? 'idle' : 'disabled',
    );
    const [socketId, setSocketId] = useState<string | null>(null);
    const [lastError, setLastError] = useState<string | null>(null);
    const [generation, setGeneration] = useState(() => getEchoGeneration());

    useEffect(() => {
        const onReconnected = () => setGeneration(getEchoGeneration());

        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, []);

    useEffect(() => {
        const echo = getEcho();

        if (!echo) {
            setStatus(config.enabled ? 'idle' : 'disabled');

            return;
        }

        const connector = echo.connector as {
            pusher?: {
                connection?: {
                    bind: (
                        event: string,
                        cb: (...args: unknown[]) => void,
                    ) => void;
                    unbind: (
                        event: string,
                        cb?: (...args: unknown[]) => void,
                    ) => void;
                    socket_id?: string;
                };
            };
        };

        const connection = connector.pusher?.connection;

        if (!connection) {
            setStatus('unavailable');

            return;
        }

        const sync = () => {
            setStatus(readPusherState(echo));
            setSocketId(connection.socket_id ?? echo.socketId() ?? null);
        };

        const onError = (...args: unknown[]) => {
            setLastError(formatConnectionError(args[0]));
            sync();
        };

        const onConnected = () => {
            setLastError(null);
            sync();
        };

        sync();
        connection.bind('state_change', sync);
        connection.bind('connected', onConnected);
        connection.bind('disconnected', sync);
        connection.bind('unavailable', sync);
        connection.bind('failed', sync);
        connection.bind('error', onError);

        return () => {
            connection.unbind('state_change', sync);
            connection.unbind('connected', onConnected);
            connection.unbind('disconnected', sync);
            connection.unbind('unavailable', sync);
            connection.unbind('failed', sync);
            connection.unbind('error', onError);
        };
    }, [config.enabled, generation]);

    const refresh = useCallback(() => {
        setStatus(readPusherState(getEcho()));
    }, []);

    const reconnect = useCallback(() => {
        setLastError(null);
        setStatus('connecting');
        reconnectEcho();
        setGeneration(getEchoGeneration());
        setStatus(readPusherState(getEcho()));
    }, []);

    return {
        echo: getEcho(),
        status,
        socketId,
        lastError,
        enabled: config.enabled,
        config,
        refresh,
        reconnect,
    };
}
