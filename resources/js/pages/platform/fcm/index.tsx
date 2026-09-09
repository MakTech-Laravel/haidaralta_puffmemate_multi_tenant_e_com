import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { listenForegroundMessages, requestFcmToken } from '@/lib/fcm';
import { getFirebaseWebConfig } from '@/lib/firebase';

type PlatformUserOption = {
    id: number;
    name: string;
    email: string;
    token_count: number;
};

type LogLevel = 'info' | 'success' | 'warn' | 'error';
type LogEntry = { id: string; level: LogLevel; message: string; ts: string };
type SendMode = 'token' | 'user';
type RecipientMode = 'single' | 'multiple';

type Props = {
    users: PlatformUserOption[];
    firebaseConfigured: boolean;
};

function now() {
    return new Date().toLocaleTimeString('en-US', { hour12: false });
}

function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export default function FcmIndex({ users, firebaseConfigured }: Props) {
    const [logs, setLogs] = useState<LogEntry[]>([]);
    const [busy, setBusy] = useState(false);
    const [generatedToken, setGeneratedToken] = useState('');
    const [tokenInput, setTokenInput] = useState('');
    const [tokensMulti, setTokensMulti] = useState('');
    const [userId, setUserId] = useState<string>(
        users[0] ? String(users[0].id) : '',
    );
    const [userIdsMulti, setUserIdsMulti] = useState('');
    const [title, setTitle] = useState('FCM test');
    const [body, setBody] = useState('Hello from Puffonmate');
    const [sendMode, setSendMode] = useState<SendMode>('token');
    const [recipientMode, setRecipientMode] =
        useState<RecipientMode>('single');
    const [lastResult, setLastResult] = useState<string | null>(null);
    const idRef = useRef(0);

    const addLog = useCallback((level: LogLevel, message: string) => {
        setLogs((prev) => [
            ...prev.slice(-99),
            { id: `${idRef.current++}`, level, message, ts: now() },
        ]);
    }, []);

    useEffect(() => {
        const config = getFirebaseWebConfig();

        if (!config) {
            addLog('warn', 'VITE_FIREBASE_* env incomplete — token generation disabled');

            return;
        }

        addLog('info', `Firebase project: ${config.projectId}`);

        let unsubscribe = () => {};

        void listenForegroundMessages((payload) => {
            const titleText =
                payload.notification?.title ?? payload.data?.title ?? 'FCM';
            const bodyText =
                payload.notification?.body ?? payload.data?.body ?? '';
            toast.info(String(titleText), { description: String(bodyText) });
            addLog('success', `Foreground message: ${titleText}`);
        }).then((stop) => {
            unsubscribe = stop;
        });

        return () => unsubscribe();
    }, [addLog]);

    const generateToken = useCallback(async () => {
        setBusy(true);
        addLog('info', 'Requesting notification permission + FCM token…');

        try {
            const token = await requestFcmToken();

            if (!token) {
                throw new Error(
                    `Permission is ${Notification.permission} or Firebase config/SW failed`,
                );
            }

            setGeneratedToken(token);
            setTokenInput(token);
            addLog('success', `Token generated (${token.slice(0, 24)}…)`);
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Token generation failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [addLog]);

    const registerToken = useCallback(async () => {
        const token = generatedToken || tokenInput;

        if (!token) {
            addLog('warn', 'Generate or paste a token first');

            return;
        }

        setBusy(true);
        addLog('info', 'POST /fcm/register …');

        try {
            const response = await fetch('/fcm/register', {
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
            const json = await response.json();
            setLastResult(JSON.stringify(json, null, 2));

            if (!response.ok) {
                throw new Error(json.message ?? `HTTP ${response.status}`);
            }

            addLog('success', 'Token registered for current user');
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Register failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [addLog, generatedToken, tokenInput]);

    const unregisterToken = useCallback(async () => {
        const token = generatedToken || tokenInput;

        if (!token) {
            addLog('warn', 'No token to unregister');

            return;
        }

        setBusy(true);
        addLog('info', 'DELETE /fcm/unregister …');

        try {
            const response = await fetch('/fcm/unregister', {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ token }),
            });
            const json = await response.json();
            setLastResult(JSON.stringify(json, null, 2));

            if (!response.ok) {
                throw new Error(json.message ?? `HTTP ${response.status}`);
            }

            addLog('success', json.message ?? 'Unregistered');
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Unregister failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [addLog, generatedToken, tokenInput]);

    const sendTest = useCallback(async () => {
        setBusy(true);
        addLog('info', 'POST /platform/fcm/test …');

        const payload: Record<string, unknown> = {
            title,
            body,
            mode: sendMode,
        };

        if (sendMode === 'token') {
            if (recipientMode === 'single') {
                payload.token = tokenInput.trim();
            } else {
                payload.tokens = tokensMulti
                    .split(/\r?\n/)
                    .map((line) => line.trim())
                    .filter(Boolean);
            }
        } else if (recipientMode === 'single') {
            payload.user_ids = userId ? [Number(userId)] : [];
        } else {
            payload.user_ids = userIdsMulti
                .split(/[\s,]+/)
                .map((id) => Number(id.trim()))
                .filter((id) => id > 0);
        }

        try {
            const response = await fetch('/platform/fcm/test', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const json = await response.json();
            setLastResult(JSON.stringify(json, null, 2));

            if (!response.ok) {
                const firstError = json.errors
                    ? Object.values(json.errors).flat()[0]
                    : null;
                throw new Error(
                    (typeof firstError === 'string' ? firstError : null) ??
                        json.message ??
                        `HTTP ${response.status}`,
                );
            }

            addLog('success', json.message ?? 'Send finished');
        } catch (err) {
            const msg = err instanceof Error ? err.message : String(err);
            addLog('error', `Send failed: ${msg}`);
        } finally {
            setBusy(false);
        }
    }, [
        addLog,
        body,
        recipientMode,
        sendMode,
        title,
        tokenInput,
        tokensMulti,
        userId,
        userIdsMulti,
    ]);

    return (
        <>
            <Head title="FCM Console" />

            <div className="mx-auto max-w-5xl space-y-4 p-6 font-mono text-sm">
                <header className="rounded-lg border border-border bg-card p-4">
                    <h1 className="text-lg font-bold text-foreground">
                        Firebase Cloud Messaging Console
                    </h1>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Generate a web FCM token, register it, and send test
                        pushes by token(s) or user(s).
                    </p>
                    {!firebaseConfigured ? (
                        <p className="mt-2 text-xs text-red-600">
                            Set VITE_FIREBASE_* (including VAPID key) in .env and
                            restart Vite / rebuild.
                        </p>
                    ) : null}
                </header>

                <section className="space-y-3 rounded-lg border border-border bg-card p-4">
                    <h2 className="font-semibold text-foreground">
                        Device / token tools
                    </h2>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            disabled={busy || !firebaseConfigured}
                            onClick={() => void generateToken()}
                            className="rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90 disabled:opacity-40"
                        >
                            Generate FCM token
                        </button>
                        <button
                            type="button"
                            disabled={busy}
                            onClick={() => void registerToken()}
                            className="rounded border border-primary px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/5 disabled:opacity-40"
                        >
                            Register token
                        </button>
                        <button
                            type="button"
                            disabled={busy}
                            onClick={() => void unregisterToken()}
                            className="rounded border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted disabled:opacity-40"
                        >
                            Unregister token
                        </button>
                    </div>
                    <textarea
                        readOnly
                        value={generatedToken}
                        placeholder="Generated token appears here"
                        className="min-h-24 w-full rounded border border-border bg-muted/40 p-2 text-xs break-all"
                    />
                </section>

                <section className="space-y-3 rounded-lg border border-border bg-card p-4">
                    <h2 className="font-semibold text-foreground">
                        Send test push
                    </h2>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => setSendMode('token')}
                            className={`rounded px-3 py-1.5 text-xs font-medium ${
                                sendMode === 'token'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'border border-border hover:bg-muted'
                            }`}
                        >
                            By FCM token(s)
                        </button>
                        <button
                            type="button"
                            onClick={() => setSendMode('user')}
                            className={`rounded px-3 py-1.5 text-xs font-medium ${
                                sendMode === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'border border-border hover:bg-muted'
                            }`}
                        >
                            By user(s)
                        </button>
                        <button
                            type="button"
                            onClick={() => setRecipientMode('single')}
                            className={`rounded px-3 py-1.5 text-xs font-medium ${
                                recipientMode === 'single'
                                    ? 'bg-accent text-accent-foreground'
                                    : 'border border-border hover:bg-muted'
                            }`}
                        >
                            Single
                        </button>
                        <button
                            type="button"
                            onClick={() => setRecipientMode('multiple')}
                            className={`rounded px-3 py-1.5 text-xs font-medium ${
                                recipientMode === 'multiple'
                                    ? 'bg-accent text-accent-foreground'
                                    : 'border border-border hover:bg-muted'
                            }`}
                        >
                            Multiple
                        </button>
                    </div>

                    {sendMode === 'token' && recipientMode === 'single' ? (
                        <input
                            value={tokenInput}
                            onChange={(e) => setTokenInput(e.target.value)}
                            placeholder="FCM device token"
                            className="w-full rounded border border-border bg-background px-3 py-2 text-xs"
                        />
                    ) : null}

                    {sendMode === 'token' && recipientMode === 'multiple' ? (
                        <textarea
                            value={tokensMulti}
                            onChange={(e) => setTokensMulti(e.target.value)}
                            placeholder="One FCM token per line"
                            className="min-h-28 w-full rounded border border-border bg-background p-2 text-xs"
                        />
                    ) : null}

                    {sendMode === 'user' && recipientMode === 'single' ? (
                        <select
                            value={userId}
                            onChange={(e) => setUserId(e.target.value)}
                            className="w-full rounded border border-border bg-background px-3 py-2 text-xs"
                        >
                            {users.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name} ({u.email}) — {u.token_count}{' '}
                                    token(s)
                                </option>
                            ))}
                        </select>
                    ) : null}

                    {sendMode === 'user' && recipientMode === 'multiple' ? (
                        <textarea
                            value={userIdsMulti}
                            onChange={(e) => setUserIdsMulti(e.target.value)}
                            placeholder="User ids, comma or space separated"
                            className="min-h-20 w-full rounded border border-border bg-background p-2 text-xs"
                        />
                    ) : null}

                    <div className="grid gap-2 sm:grid-cols-2">
                        <input
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder="Title"
                            className="rounded border border-border bg-background px-3 py-2 text-xs"
                        />
                        <input
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            placeholder="Body"
                            className="rounded border border-border bg-background px-3 py-2 text-xs"
                        />
                    </div>

                    <button
                        type="button"
                        disabled={busy}
                        onClick={() => void sendTest()}
                        className="rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90 disabled:opacity-40"
                    >
                        Send test push
                    </button>
                </section>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
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

                    <section className="rounded-lg border border-border bg-card p-3">
                        <h2 className="mb-2 font-semibold text-foreground">
                            Last API response
                        </h2>
                        <pre className="h-64 overflow-auto whitespace-pre-wrap break-all rounded bg-muted/40 p-2 text-xs text-foreground">
                            {lastResult ?? 'No response yet.'}
                        </pre>
                    </section>
                </div>
            </div>
        </>
    );
}

FcmIndex.layout = {
    breadcrumbs: [
        {
            title: 'FCM Console',
            href: '/platform/fcm',
        },
    ],
};
