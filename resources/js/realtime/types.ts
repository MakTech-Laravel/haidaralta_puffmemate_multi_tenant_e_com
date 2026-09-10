export type ConnectionStatus =
    | 'disabled'
    | 'idle'
    | 'connecting'
    | 'connected'
    | 'unavailable'
    | 'disconnected'
    | 'failed';

export const CONNECTION_STATUS_LABELS: Record<ConnectionStatus, string> = {
    disabled: 'Disabled',
    idle: 'Idle',
    connecting: 'Connecting…',
    connected: 'Connected',
    unavailable: 'Unavailable',
    disconnected: 'Disconnected',
    failed: 'Failed',
};

export type RealtimeTestPayload = {
    message?: string;
    triggered_at?: string;
    user_id?: number;
};

export type DatabaseBackupHistoryUpdatedPayload = {
    id: number;
    type: string;
    status: string;
    filename?: string | null;
    message?: string | null;
};
