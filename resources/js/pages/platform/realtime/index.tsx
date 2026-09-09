import { Head, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { getEcho, getEchoGeneration, getReverbClientConfig } from '@/echo';
import { useEchoConnection } from '@/hooks/use-echo-connection';
import { showRealtimeTestToast } from '@/hooks/use-private-realtime-toast';
import {
    CONNECTION_STATUS_LABELS,
    type RealtimeTestPayload,
} from '@/realtime/types';
import {
    health as realtimeHealth,
    ping as realtimePing,
    testNotification as realtimeTestNotification,
} from '@/routes/platform/realtime';
import type { Auth } from '@/types/auth';

type LogLevel = 'info' | 'success' | 'warn' | 'error';
type LogEntry = { id: string; level: LogLevel; message: string; ts: string };
type ReceivedEvent = {
    id: string;
    channel: string;
    event: string;
    data: unknown;
    ts: string;
};

function now() {
    return new Date().toLocaleTimeString('en-US', { hour12: false });
}

function uid() {
    return Math.random().toString(36).slice(2, 9);
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

const STATUS_DOT: Record<string, string> = {
    connected: 'bg-green-500',
    connecting: 'bg-yellow-500 animate-pulse',
    unavailable: 'bg-orange-500',
    disconnected: 'bg-orange-500',
    failed: 'bg-red-500',
    idle: 'bg-gray-400',
    disabled: 'bg-gray-400',
};

export default function RealtimeIndex() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;
    const { status, socketId, enabled, config, lastError, reconnect } =
        useEchoConnection();
    const [events, setEvents] = useState<ReceivedEvent[]>([]);
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [apiResponse, setApiResponse] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [echoGeneration, setEchoGeneration] = useState(() =>
        getEchoGeneration(),
    );
    const idRef = useRef(0);

    const addLog = useCallback((level: LogLevel, message: string) => {
        setLogs((prev) => [
            ...prev.slice(-99),
            { id: `${idRef.current++}`, level, message, ts: now() },
        ]);
    }, []);

    useEffect(() => {
        const onReconnected = () => {
            setEchoGeneration(getEchoGeneration());
            addLog('info', 'Echo client recreated — reconnecting WebSocket');
        };

        window.addEventListener('echo:reconnected', onReconnected);

        return () => {
            window.removeEventListener('echo:reconnected', onReconnected);
        };
    }, [addLog]);

    useEffect(() => {
        if (!lastError) {
            return;
        }

        addLog('error', lastError);
    }, [lastError, addLog]);

    useEffect(() => {
        const echo = getEcho();

        if (!echo || !enabled) {
            return;
        }

        const publicChannel = echo.channel('reverb-ping');
        const onPing = (data: {
            message?: string;
            triggered_at?: string;
            source?: string;
        }) => {
            setEvents((prev) =>
                [
                    {
                        id: uid(),
                        channel: 'reverb-ping',
                        event: 'ping',
                        data,
                        ts: now(),
                    },
                    ...prev,
                ].slice(0, 50),
            );
            addLog('success', `Public event received: ${data.message ?? ''}`);
        };

        publicChannel.listen('.ping', onPing);

        return () => {
            try {
                publicChannel.stopListening('.ping', onPing);
            } catch {
                // ignore
            }
            echo.leave('reverb-ping');
        };
    }, [enabled, addLog, echoGeneration]);

    useEffect(() => {
        if (!user?.id) {
            return;
        }

        const echo = getEcho();

        if (!echo) {
            return;
        }

        const channelName = `App.Models.User.${user.id}`;
        const channel = echo.private(channelName);

        const onPrivateTest = (data: RealtimeTestPayload) => {
            setEvents((prev) =>
                [
                    {
                        id: uid(),
                        channel: channelName,
                        event: 'realtime.test',
                        data,
                        ts: now(),
                    },
                    ...prev,
                ].slice(0, 50),
            );
            addLog(
                'success',
                `Private test received: ${data.message ?? 'ok'}`,
            );
            showRealtimeTestToast(data);
        };

        channel.listen('.realtime.test', onPrivateTest);

        return () => {
            try {
                channel.stopListening('.realtime.test', onPrivateTest);
            } catch {
                // ignore
            }
            // Do not echo.leave(private user channel) — the global toast
            // subscriber in usePrivateRealtimeToast shares this channel.
        };
    }, [user?.id, addLog, echoGeneration]);

    const firePublicPing = useCallback(async () => {
        setBusy(true);
        addLog('info', 'GET /platform/realtime/ping …');

        try {
            const response = await fetch(
                realtimePing.url({ query: { message: `Ping ${now()}` } }),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );
            const json = await response.json();
            setApiResponse(JSON.stringify(json, null, 2));

            if (!response.ok) {
                throw new Error(json.message ?? `HTTP ${response.status}`);
            }

            addLog(
                'success',
                'Backend dispatched public ping — awaiting WebSocket push',
            );
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            setApiResponse(`ERROR: ${msg}`);
            addLog('error', `Ping failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [addLog]);

    const checkConfig = useCallback(async () => {
        addLog('info', 'GET /platform/realtime/health …');

        try {
            const response = await fetch(realtimeHealth.url(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const json = await response.json();
            setApiResponse(JSON.stringify(json, null, 2));

            if (!response.ok) {
                throw new Error(json.message ?? `HTTP ${response.status}`);
            }

            addLog('success', 'Fetched backend broadcasting config');
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Config fetch failed: ${msg}`);
        }
    }, [addLog]);

    const firePrivate = useCallback(async () => {
        setBusy(true);
        addLog('info', 'POST /platform/realtime/test-notification …');

        try {
            const response = await fetch(realtimeTestNotification.url(), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
            });
            const json = await response.json();
            setApiResponse(JSON.stringify(json, null, 2));

            if (!response.ok) {
                throw new Error(json.message ?? `HTTP ${response.status}`);
            }

            addLog(
                'success',
                'Private test dispatched — awaiting private push',
            );
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Private test failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [addLog]);

    const connected = status === 'connected';
    const clientConfig = getReverbClientConfig();

    return (
        <>
            <Head title="Realtime Console" />

            <div className="mx-auto max-w-5xl space-y-4 p-6 font-mono text-sm">
                <header className="rounded-lg border border-border bg-card p-4">
                    <h1 className="text-lg font-bold text-foreground">
                        Reverb WebSocket Console
                    </h1>
                    <p className="mt-1 text-xs text-muted-foreground">
                        End-to-end test: Puffmemate ⇄
                        ws.puffmemate.maktechlaravel.cloud ⇄ browser Echo
                    </p>
                </header>

                <section className="space-y-3 rounded-lg border border-border bg-card p-4">
                    <div className="flex items-center gap-3">
                        <span
                            className={`h-3 w-3 rounded-full ${STATUS_DOT[status] ?? 'bg-gray-400'}`}
                        />
                        <span className="font-semibold text-foreground">
                            {CONNECTION_STATUS_LABELS[status]}
                        </span>
                        {socketId ? (
                            <span className="text-xs text-muted-foreground">
                                socket_id:{' '}
                                <span className="text-green-600">
                                    {socketId}
                                </span>
                            </span>
                        ) : null}
                    </div>

                    {lastError ? (
                        <p className="text-xs text-red-600">{lastError}</p>
                    ) : null}

                    {(status === 'failed' ||
                        status === 'unavailable' ||
                        status === 'disconnected') && (
                        <p className="text-xs text-muted-foreground">
                            Pusher stops retrying after{' '}
                            <span className="font-semibold">Failed</span>. If
                            the Reverb container just came back, click Reconnect
                            (or hard-refresh).
                        </p>
                    )}

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => {
                                addLog('info', 'Manual reconnect requested');
                                reconnect();
                            }}
                            disabled={!enabled}
                            className="rounded border border-primary px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/5 disabled:opacity-40"
                        >
                            Reconnect
                        </button>
                        <button
                            type="button"
                            onClick={firePublicPing}
                            disabled={busy || !connected}
                            className="rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90 disabled:opacity-40"
                        >
                            Trigger public ping
                        </button>
                        <button
                            type="button"
                            onClick={firePrivate}
                            disabled={busy || !connected || !user}
                            className="rounded bg-purple-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-purple-500 disabled:opacity-40"
                        >
                            Trigger private test
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                showRealtimeTestToast({
                                    message: `Local Sonner test at ${new Date().toISOString()}`,
                                    triggered_at: new Date().toISOString(),
                                    user_id: user?.id,
                                });
                                addLog('info', 'Local Sonner toast requested');
                            }}
                            className="rounded border border-amber-600 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40"
                        >
                            Test toast UI
                        </button>
                        <button
                            type="button"
                            onClick={checkConfig}
                            className="rounded border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted"
                        >
                            Check backend config
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setEvents([]);
                                setLogs([]);
                                setApiResponse(null);
                            }}
                            className="rounded border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted"
                        >
                            Clear
                        </button>
                    </div>

                    <div className="grid grid-cols-1 gap-x-4 gap-y-1 border-t border-border pt-2 text-xs text-muted-foreground sm:grid-cols-2">
                        <div>
                            WS Host:{' '}
                            <span className="text-foreground">
                                {config.host || 'MISSING'}
                            </span>
                        </div>
                        <div>
                            WS Port:{' '}
                            <span className="text-foreground">
                                {config.port}
                            </span>
                        </div>
                        <div>
                            Scheme:{' '}
                            <span className="text-foreground">
                                {config.scheme}
                            </span>
                        </div>
                        <div>
                            forceTLS:{' '}
                            <span className="text-foreground">
                                {String(config.useTls)}
                            </span>
                        </div>
                        <div>
                            Transports:{' '}
                            <span className="text-foreground">
                                {clientConfig.enabledTransports.join(', ')}
                            </span>
                        </div>
                        <div>
                            App Key:{' '}
                            <span className="text-foreground">
                                {config.appKey
                                    ? `${config.appKey.slice(0, 8)}…`
                                    : 'MISSING'}
                            </span>
                        </div>
                        <div className="sm:col-span-2">
                            Auth endpoint:{' '}
                            <span className="text-foreground">
                                {config.authEndpoint}
                            </span>
                        </div>
                    </div>
                </section>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <section className="rounded-lg border border-border bg-card p-3">
                        <h2 className="mb-2 font-semibold text-foreground">
                            Received events ({events.length})
                        </h2>
                        <div className="h-64 space-y-2 overflow-y-auto rounded bg-muted/40 p-2">
                            {events.length === 0 ? (
                                <p className="mt-8 text-center text-muted-foreground">
                                    No events yet.
                                </p>
                            ) : (
                                events.map((ev) => (
                                    <div
                                        key={ev.id}
                                        className="rounded border border-border bg-card p-2 text-xs"
                                    >
                                        <div className="mb-1 flex justify-between text-muted-foreground">
                                            <span className="text-green-600">
                                                {ev.channel} → {ev.event}
                                            </span>
                                            <span>{ev.ts}</span>
                                        </div>
                                        <pre className="whitespace-pre-wrap break-all text-foreground">
                                            {JSON.stringify(ev.data, null, 2)}
                                        </pre>
                                    </div>
                                ))
                            )}
                        </div>
                    </section>

                    <section className="rounded-lg border border-border bg-card p-3">
                        <h2 className="mb-2 font-semibold text-foreground">
                            Debug log ({logs.length})
                        </h2>
                        <div className="h-64 space-y-0.5 overflow-y-auto rounded bg-muted/40 p-2">
                            {logs.length === 0 ? (
                                <p className="mt-8 text-center text-muted-foreground">
                                    No log entries.
                                </p>
                            ) : (
                                logs.map((l) => (
                                    <div
                                        key={l.id}
                                        className="flex gap-2 text-xs leading-5"
                                    >
                                        <span className="shrink-0 text-muted-foreground">
                                            {l.ts}
                                        </span>
                                        <span
                                            className={
                                                l.level === 'error'
                                                    ? 'text-red-500'
                                                    : l.level === 'warn'
                                                      ? 'text-yellow-600'
                                                      : l.level === 'success'
                                                        ? 'text-green-600'
                                                        : 'text-blue-500'
                                            }
                                        >
                                            [{l.level.toUpperCase()}]
                                        </span>
                                        <span className="break-all text-foreground">
                                            {l.message}
                                        </span>
                                    </div>
                                ))
                            )}
                        </div>
                    </section>
                </div>

                {apiResponse ? (
                    <section className="rounded-lg border border-border bg-card p-3">
                        <h2 className="mb-2 font-semibold text-foreground">
                            Last API response
                        </h2>
                        <pre
                            className={`whitespace-pre-wrap break-all rounded bg-muted/40 p-2 text-xs ${
                                apiResponse.startsWith('ERROR')
                                    ? 'text-red-500'
                                    : 'text-green-600'
                            }`}
                        >
                            {apiResponse}
                        </pre>
                    </section>
                ) : null}
            </div>
        </>
    );
}
