/**
 * FileUpload.tsx
 * ─────────────────────────────────────────────────────────────────────────────
 * Production-ready, fully-typed file-upload component for Laravel + Inertia.
 *
 * Features
 *  • Single & multiple file mode
 *  • Drag-and-drop + click-to-browse
 *  • Image / video / document card previews
 *  • Existing (server-side) file display with individual remove
 *  • Real-time circular progress — per-file OR aggregate
 *  • Indeterminate pulse loading when no numeric progress is available
 *  • Cancel in-flight uploads (Inertia `cancel()` / `cancelAll()`)
 *  • Scalable previews via Object URLs (revoked automatically — no base64 blow-up)
 *  • Client-side validation: per-file size, total count, `accept` type, de-dup
 *    → reported through `onReject` + optional inline messages (no `alert()`)
 *  • Optional lightbox (yet-another-react-lightbox) — disabled by default
 *  • Every element styleable via `classNames` prop
 *  • Imperative handle: openFileBrowser() / clearFiles() via ref
 *  • Full ARIA / keyboard accessibility
 *
 * Dependencies
 *  npm install yet-another-react-lightbox lucide-react
 *  (shadcn/ui Badge + Button must already be present)
 */

import {
    AlertCircle,
    CheckCircle2,
    File as FileIcon,
    FileImage,
    FileText,
    FileVideo,
    Upload,
    X,
} from 'lucide-react';
import type { ChangeEvent, DragEvent } from 'react';
import React, {
    forwardRef,
    useCallback,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';

import Lightbox from 'yet-another-react-lightbox';
import Video from 'yet-another-react-lightbox/plugins/video';
import Zoom from 'yet-another-react-lightbox/plugins/zoom';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

import 'yet-another-react-lightbox/styles.css';

// ─── Constants ────────────────────────────────────────────────────────────────

const BYTES_PER_MB = 1_048_576;

const MIME_ICONS: Record<string, React.ElementType> = {
    'application/pdf': FileText,
    'text/csv': FileText,
    'text/plain': FileText,
    'application/msword': FileText,
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        FileText,
    'application/vnd.ms-excel': FileText,
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        FileText,
};

const MIME_EXT_LABELS: Record<string, string> = {
    'application/pdf': 'PDF',
    'text/csv': 'CSV',
    'text/plain': 'TXT',
    'application/msword': 'DOC',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        'DOCX',
    'application/vnd.ms-excel': 'XLS',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
};

// ─── Public types ─────────────────────────────────────────────────────────────

/** A file that already exists on the server. */
export interface ExistingFile {
    id: number | string;
    /** Storage path — used as fallback display name. */
    path: string;
    /** Public URL for preview / download. */
    url: string;
    mime_type: string;
    name?: string;
    size?: number;
}

/**
 * Per-file upload progress (0–100), one entry per file.
 * Use this when you send each file in its own Inertia `router.post()` call
 * so every card renders its own individual progress ring.
 *
 * Key each entry with {@link fileProgressKey} so two files that happen to share
 * a name still get independent rings. Looking up by bare `File.name` is also
 * supported (legacy fallback) but collides when names repeat.
 *
 * @example
 * import { fileProgressKey } from '@/components/file-upload';
 *
 * files.forEach(file => {
 *   const key = fileProgressKey(file);
 *   router.post('/upload', { file }, {
 *     forceFormData: true,
 *     onProgress: (e) =>
 *       setFileProgresses(prev => ({ ...prev, [key]: e.percentage ?? 0 })),
 *   });
 * });
 */
export type FileProgresses = Record<string, number>;

/** Why a file was skipped during selection / drop. */
export type FileRejectionReason = 'size' | 'type' | 'count' | 'duplicate';

/** A single file the component refused to accept. */
export interface FileRejection {
    file: File;
    reason: FileRejectionReason;
    /** Human-readable, ready to display. */
    message: string;
}

/** Per-slot className overrides — pass only what you need. */
export interface FileUploadClassNames {
    /** Root wrapper `<div>` */
    wrapper?: string;
    /** Drag-and-drop / click-to-browse zone */
    dropzone?: string;
    /** Flex column inside the drop zone */
    dropzoneContent?: string;
    /** Grid that holds all file cards */
    grid?: string;
    /** Individual file card */
    card?: string;
    /** Thumbnail area of a card */
    cardMedia?: string;
    /** Filename + size text row */
    cardInfo?: string;
    /** Remove (×) button */
    removeButton?: string;
    /** Semi-transparent progress overlay on a card */
    progressOverlay?: string;
    /** Aggregate / indeterminate progress row */
    aggregateProgress?: string;
    /** Validation / rejection message text */
    error?: string;
}

export type FileUploadVariant = 'default' | 'avatar';

export interface FileUploadProps {
    // ── Value ─────────────────────────────────────────────────────────────────
    value?: File | File[] | null;
    onChange: (files: File | File[] | null) => void;

    // ── Existing (server-side) files ──────────────────────────────────────────
    existingFiles?: ExistingFile[];
    onRemoveExisting?: (id: number | string) => void;

    // ── Input behaviour ───────────────────────────────────────────────────────
    multiple?: boolean;
    /** Comma-separated accept list. Enforced on BOTH click AND drag-drop. */
    accept?: string;
    /** Per-file maximum in MB. Default: `10`. */
    maxSize?: number;
    /** Maximum total files (multiple mode). */
    maxFiles?: number;
    /**
     * Skip an identical file that is already in the current selection
     * (matched by name + size + lastModified). Does NOT compare against existing
     * server files — same-named uploads from different users are always allowed.
     * Default: `true`.
     */
    dedupe?: boolean;
    disabled?: boolean;
    required?: boolean;

    // ── Upload / progress ─────────────────────────────────────────────────────
    /**
     * `true` while a request is in-flight.
     * Disables remove buttons and shows a loading ring even when no numeric
     * progress is supplied.
     */
    isUploading?: boolean;
    /**
     * Aggregate progress 0–100 for a single multipart POST.
     * Typically: `form.progress?.percentage ?? null` from Inertia `useForm`.
     */
    uploadProgress?: number | null;
    /**
     * Per-file progress keyed by `File.name` (0–100).
     * Each card renders its own circular ring.
     * Use with the per-file `router.post()` pattern.
     */
    fileProgresses?: FileProgresses;
    /**
     * When provided AND `isUploading` is true, a Cancel control is rendered on
     * the progress UI. Wire this to Inertia `form.cancel()` / `router.cancelAll()`.
     */
    onCancel?: () => void;

    // ── Validation feedback ─────────────────────────────────────────────────────
    /**
     * Called with the files that were skipped during selection / drop.
     * Use this for toasts or custom UI. Replaces the old blocking `alert()`.
     */
    onReject?: (rejections: FileRejection[]) => void;
    /** Render skipped-file messages inline below the drop zone. Default: `true`. */
    showRejections?: boolean;

    // ── Lightbox ──────────────────────────────────────────────────────────────
    /**
     * Enable the yet-another-react-lightbox viewer for image / video cards.
     *
     * Default: `false` — clicking media cards does nothing (no lightbox).
     * Set `true` to let users open a full-screen viewer by clicking any
     * image or video thumbnail.
     */
    lightbox?: boolean;

    // ── Display ───────────────────────────────────────────────────────────────
    placeholder?: string;
    hint?: string;
    /** Circular avatar picker — compact preview, no full-width card grid. */
    variant?: FileUploadVariant;
    /** Diameter in pixels when `variant="avatar"`. Default: `112`. */
    avatarSize?: number;
    /** Shown inside the empty avatar ring before a file is selected. */
    avatarFallback?: React.ReactNode;

    // ── Styling ───────────────────────────────────────────────────────────────
    /** Alias for `classNames.wrapper`. */
    className?: string;
    classNames?: FileUploadClassNames;
    error?: string;
}

/** Methods exposed via `ref`. */
export interface FileUploadHandle {
    /** Programmatically open the OS file browser. */
    openFileBrowser: () => void;
    /** Clear all newly-selected (local) files. */
    clearFiles: () => void;
}

// ─── Internal types ───────────────────────────────────────────────────────────

interface FilePreview {
    /** Stable React list key. */
    key: string;
    file: File;
    /** Object URL for images/video; empty string for other types. */
    preview: string;
    type: 'image' | 'video' | 'other';
}

// ─── Utility helpers ──────────────────────────────────────────────────────────

function resolveFileType(mime: string): 'image' | 'video' | 'other' {
    if (mime.startsWith('image/')) {
        return 'image';
    }

    if (mime.startsWith('video/')) {
        return 'video';
    }

    return 'other';
}

function resolveIcon(mime: string): React.ElementType {
    if (mime.startsWith('image/')) {
        return FileImage;
    }

    if (mime.startsWith('video/')) {
        return FileVideo;
    }

    return MIME_ICONS[mime] ?? FileIcon;
}

function resolveExtLabel(mime: string): string {
    if (mime.startsWith('image/')) {
        return (mime.split('/')[1] ?? 'IMG').toUpperCase();
    }

    if (mime.startsWith('video/')) {
        return (mime.split('/')[1] ?? 'VID').toUpperCase();
    }

    return MIME_EXT_LABELS[mime] ?? 'FILE';
}

function formatBytes(bytes: number): string {
    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));

    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`;
}

/**
 * Stable per-file identity (`name::size::lastModified`).
 *
 * Use it as the key for {@link FileProgresses} so two different files that share
 * a name still get independent progress rings. It also backs de-duplication
 * within the current selection — only an identical re-pick of the exact same
 * file collides; different files that happen to share a name do not.
 */
export function fileProgressKey(file: File): string {
    return `${file.name}::${file.size}::${file.lastModified}`;
}

/**
 * Mirror the browser's `accept` matching so drag-dropped files honour the same
 * filter as the OS dialog. Supports `.ext`, `type/*`, and exact `type/subtype`.
 */
function matchesAccept(file: File, accept?: string): boolean {
    if (!accept) {
        return true;
    }

    const tokens = accept
        .split(',')
        .map((t) => t.trim().toLowerCase())
        .filter(Boolean);

    if (!tokens.length) {
        return true;
    }

    const name = file.name.toLowerCase();
    const mime = file.type.toLowerCase();

    return tokens.some((token) => {
        if (token.startsWith('.')) {
            return name.endsWith(token);
        }

        if (token.endsWith('/*')) {
            return mime.startsWith(token.slice(0, -1));
        }

        return mime === token;
    });
}

/** Build a lightweight preview. Object URLs reference the file — no base64 copy. */
function buildPreview(file: File): FilePreview {
    const type = resolveFileType(file.type);
    const key = `${file.name}-${file.size}-${Math.random().toString(36).slice(2)}`;
    const preview = type === 'other' ? '' : URL.createObjectURL(file);

    return { key, file, preview, type };
}

function revokePreview(p: FilePreview): void {
    if (p.preview) {
        URL.revokeObjectURL(p.preview);
    }
}

// ─── CircularProgress ─────────────────────────────────────────────────────────

interface CircularProgressProps {
    /** 0–100 */
    value: number;
    size?: number;
    strokeWidth?: number;
    /** Spinning indeterminate ring — ignores `value`. */
    indeterminate?: boolean;
    /**
     * `'overlay'` — white ring + white label on dark card overlay (default).
     * `'inline'`  — themed ring for light-background rows.
     */
    theme?: 'overlay' | 'inline';
    className?: string;
}

function CircularProgress({
    value,
    size = 48,
    strokeWidth = 4,
    indeterminate = false,
    theme = 'overlay',
    className,
}: CircularProgressProps) {
    const r = (size - strokeWidth) / 2;
    const circ = 2 * Math.PI * r;
    const offset = circ - (Math.max(0, Math.min(100, value)) / 100) * circ;
    const c = size / 2;
    const isOverlay = theme === 'overlay';

    return (
        <div
            className={cn(
                'relative inline-flex items-center justify-center',
                className,
            )}
            role="progressbar"
            aria-valuenow={indeterminate ? undefined : Math.round(value)}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label={
                indeterminate ? 'Uploading…' : `${Math.round(value)}% uploaded`
            }
        >
            <svg
                width={size}
                height={size}
                viewBox={`0 0 ${size} ${size}`}
                style={{ transform: 'rotate(-90deg)' }}
                className={cn(indeterminate && 'animate-spin')}
            >
                {/* Track */}
                <circle
                    cx={c}
                    cy={c}
                    r={r}
                    fill="none"
                    strokeWidth={strokeWidth}
                    className={isOverlay ? undefined : 'stroke-muted'}
                    style={
                        isOverlay
                            ? { stroke: 'rgba(255,255,255,0.22)' }
                            : undefined
                    }
                />
                {/* Progress arc */}
                <circle
                    cx={c}
                    cy={c}
                    r={r}
                    fill="none"
                    strokeWidth={strokeWidth}
                    strokeDasharray={circ}
                    strokeDashoffset={indeterminate ? circ * 0.72 : offset}
                    strokeLinecap="round"
                    className={isOverlay ? undefined : 'stroke-primary'}
                    style={{
                        transition:
                            'stroke-dashoffset 0.4s cubic-bezier(0.4,0,0.2,1)',
                        ...(isOverlay ? { stroke: 'white' } : {}),
                    }}
                />
            </svg>
            {!indeterminate && (
                <span
                    className={cn(
                        'absolute leading-none font-semibold tabular-nums select-none',
                        isOverlay ? 'text-white' : 'text-primary',
                    )}
                    style={{ fontSize: Math.max(9, Math.round(size * 0.21)) }}
                >
                    {Math.round(value)}%
                </span>
            )}
        </div>
    );
}

// ─── AggregateProgressRow ─────────────────────────────────────────────────────

interface AggregateProgressRowProps {
    value: number;
    indeterminate?: boolean;
    onCancel?: () => void;
    className?: string;
}

function AggregateProgressRow({
    value,
    indeterminate = false,
    onCancel,
    className,
}: AggregateProgressRowProps) {
    const capped = Math.min(100, value);

    return (
        <div
            className={cn(
                'flex items-center gap-3 rounded-lg border bg-muted/40 px-3 py-2.5',
                className,
            )}
        >
            <CircularProgress
                value={capped}
                size={38}
                strokeWidth={3.5}
                indeterminate={indeterminate}
                theme="inline"
                className="shrink-0"
            />
            <div className="min-w-0 flex-1">
                <div className="mb-1.5 flex items-center justify-between">
                    <span className="text-xs font-medium text-foreground">
                        {indeterminate
                            ? 'Uploading…'
                            : capped >= 100
                              ? 'Upload complete — processing…'
                              : `Uploading ${Math.round(capped)}%`}
                    </span>
                    {!indeterminate && capped >= 100 && (
                        <CheckCircle2 className="h-3.5 w-3.5 shrink-0 text-green-500" />
                    )}
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className={cn(
                            'h-full rounded-full bg-primary transition-all duration-300',
                            indeterminate && 'w-full animate-pulse',
                        )}
                        style={
                            !indeterminate ? { width: `${capped}%` } : undefined
                        }
                    />
                </div>
            </div>
            {onCancel && capped < 100 && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={onCancel}
                    className="h-7 shrink-0 px-2 text-xs text-muted-foreground hover:text-destructive"
                >
                    <X className="mr-1 h-3.5 w-3.5" />
                    Cancel
                </Button>
            )}
        </div>
    );
}

// ─── FileCard ─────────────────────────────────────────────────────────────────

interface FileCardProps {
    name: string;
    size?: number;
    preview: string;
    type: 'image' | 'video' | 'other';
    mime: string;
    /** Called when the user clicks the thumbnail. `undefined` = not clickable. */
    onPreview?: () => void;
    onRemove?: () => void;
    /** Called when the user cancels this in-flight upload. */
    onCancel?: () => void;
    /** Per-file progress 0–100. `undefined` = no progress ring. */
    progress?: number;
    /** Show indeterminate spinner (no numeric value). */
    loading?: boolean;
    /** Hide filename / size row — used by avatar variant. */
    compact?: boolean;
    /** Fixed square dimensions for avatar-style cards. */
    fixedSize?: number;
    classNames?: Pick<
        FileUploadClassNames,
        'card' | 'cardMedia' | 'cardInfo' | 'removeButton' | 'progressOverlay'
    >;
}

function FileCard({
    name,
    size,
    preview,
    type,
    mime,
    onPreview,
    onRemove,
    onCancel,
    progress,
    loading = false,
    compact = false,
    fixedSize,
    classNames = {},
}: FileCardProps) {
    const ext = resolveExtLabel(mime);

    const isUploading = loading || (progress !== undefined && progress < 100);
    const isDone = progress !== undefined && progress >= 100;

    const [showCheck, setShowCheck] = useState(false);
    useEffect(() => {
        if (!isDone) {
            // Upload finished or was reset — clear any lingering success flash.
            const clearT = window.setTimeout(() => setShowCheck(false), 0);

            return () => clearTimeout(clearT);
        }

        // setState only inside async callbacks — never synchronously in the body.
        const showT = window.setTimeout(() => setShowCheck(true), 0);
        const hideT = window.setTimeout(() => setShowCheck(false), 2000);

        return () => {
            clearTimeout(showT);
            clearTimeout(hideT);
        };
    }, [isDone]);

    return (
        <div
            className={cn(
                'group relative rounded-lg border bg-card shadow-sm',
                'transition-shadow hover:shadow-md',
                classNames.card,
            )}
            style={
                fixedSize ? { width: fixedSize, height: fixedSize } : undefined
            }
        >
            {/* Clip layer ─────────────────────────────────────────────────────
                Media, info, and progress/done overlays are clipped to the card
                shape (including rounded-full avatars). Action buttons render
                OUTSIDE this layer so they're never cut off by the clip. */}
            <div className="relative h-full overflow-hidden rounded-[inherit]">
                {/* Thumbnail ──────────────────────────────────────────────── */}
                <div
                    className={cn(
                        'relative flex items-center justify-center overflow-hidden bg-muted',
                        compact ? 'h-full w-full' : 'aspect-video',
                        classNames.cardMedia,
                    )}
                >
                    {type === 'image' && preview ? (
                        <img
                            src={preview}
                            alt={name}
                            draggable={false}
                            onClick={onPreview}
                            className={cn(
                                'h-full w-full object-cover',
                                onPreview ? 'cursor-zoom-in' : 'cursor-default',
                            )}
                        />
                    ) : type === 'video' && preview ? (
                        <video
                            src={preview}
                            preload="metadata"
                            onClick={onPreview}
                            className={cn(
                                'h-full w-full object-cover',
                                onPreview ? 'cursor-pointer' : 'cursor-default',
                            )}
                        />
                    ) : (
                        <div className="pointer-events-none flex flex-col items-center gap-2">
                            {React.createElement(resolveIcon(mime), {
                                className: 'h-9 w-9 text-muted-foreground',
                            })}
                            <Badge
                                variant="secondary"
                                className="px-1.5 py-0 text-[10px]"
                            >
                                {ext}
                            </Badge>
                        </div>
                    )}
                </div>

                {!compact ? (
                    <div className={cn('px-2.5 py-2', classNames.cardInfo)}>
                        <p
                            className="truncate text-xs leading-tight font-medium text-foreground"
                            title={name}
                        >
                            {name}
                        </p>
                        <div className="mt-0.5 flex items-center justify-between gap-1">
                            <span className="truncate text-[11px] text-muted-foreground">
                                {size !== undefined ? formatBytes(size) : ''}
                            </span>
                            <Badge
                                variant="outline"
                                className="pointer-events-none h-[18px] shrink-0 px-1 py-0 text-[9px]"
                            >
                                {ext}
                            </Badge>
                        </div>
                    </div>
                ) : null}

                {/* Progress overlay ───────────────────────────────────────── */}
                {isUploading && (
                    <div
                        className={cn(
                            'absolute inset-0 flex flex-col items-center justify-center gap-2',
                            'rounded-[inherit] bg-black/60 backdrop-blur-[2px]',
                            classNames.progressOverlay,
                        )}
                    >
                        {loading && progress === undefined ? (
                            <CircularProgress
                                value={0}
                                indeterminate
                                size={44}
                            />
                        ) : (
                            <CircularProgress value={progress ?? 0} size={46} />
                        )}
                        {onCancel && (
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onCancel();
                                }}
                                className="text-[11px] font-medium text-white/90 underline underline-offset-2 hover:text-white"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                )}

                {/* Done flash ─────────────────────────────────────────────── */}
                {showCheck && (
                    <div
                        className={cn(
                            'pointer-events-none absolute inset-0 flex items-center justify-center',
                            'animate-in rounded-[inherit] bg-green-900/40 duration-150 fade-in',
                        )}
                    >
                        <CheckCircle2 className="h-9 w-9 text-white drop-shadow-md" />
                    </div>
                )}
            </div>

            {/* Remove button — outside the clip layer so it stays visible even
                on circular avatar cards. ─────────────────────────────────── */}
            {onRemove && !isUploading && (
                <Button
                    type="button"
                    variant="destructive"
                    size="icon"
                    onClick={(e) => {
                        e.stopPropagation();
                        onRemove();
                    }}
                    className={cn(
                        'absolute top-1.5 right-1.5 z-10 h-6 w-6',
                        compact
                            ? 'top-0.5 right-0.5 h-7 w-7 opacity-100 shadow-md'
                            : 'opacity-0 shadow-md transition-opacity group-hover:opacity-100',
                        classNames.removeButton,
                    )}
                    aria-label={`Remove ${name}`}
                >
                    <X className="h-3 w-3" />
                </Button>
            )}
        </div>
    );
}

function avatarClassNames(
    overrides?: FileUploadClassNames,
): FileUploadClassNames {
    return {
        wrapper: 'flex flex-wrap items-start gap-4',
        card: 'rounded-full border-2 border-border/70 bg-card shadow-sm ring-1 ring-primary/10',
        cardMedia: 'h-full w-full',
        dropzone:
            'shrink-0 rounded-full border-2 border-dashed border-border/80 bg-muted/20 hover:border-primary/50 hover:bg-primary/5',
        dropzoneContent: 'flex flex-col items-center gap-0.5 px-2 text-center',
        error: 'text-sm text-destructive',
        ...overrides,
    };
}

function AvatarUploadCaption({
    title,
    description,
    fileName,
    fileSize,
}: {
    title: string;
    description?: string;
    fileName?: string;
    fileSize?: number;
}) {
    return (
        <div className="min-w-0 flex-1 space-y-1 pt-1">
            <p className="text-sm font-medium text-foreground">{title}</p>
            {fileName ? (
                <p className="truncate text-sm text-muted-foreground">
                    {fileName}
                </p>
            ) : null}
            {fileSize !== undefined ? (
                <p className="text-xs text-muted-foreground">
                    {formatBytes(fileSize)}
                </p>
            ) : null}
            {description ? (
                <p className="text-xs leading-relaxed text-muted-foreground">
                    {description}
                </p>
            ) : null}
        </div>
    );
}

// ─── FileUpload ───────────────────────────────────────────────────────────────

const FileUpload = forwardRef<FileUploadHandle, FileUploadProps>(
    function FileUpload(
        {
            value,
            onChange,
            existingFiles = [],
            onRemoveExisting,
            multiple = false,
            accept,
            maxSize = 10,
            maxFiles,
            dedupe = true,
            disabled = false,
            required = false,
            isUploading = false,
            uploadProgress,
            fileProgresses,
            onCancel,
            onReject,
            showRejections = true,
            lightbox = false, // ← default OFF
            placeholder = 'Drag & drop or click to browse',
            hint,
            variant = 'default',
            avatarSize = 112,
            avatarFallback,
            className,
            classNames = {},
            error,
        },
        ref,
    ) {
        const isAvatar = variant === 'avatar';
        const mergedClassNames = isAvatar
            ? avatarClassNames(classNames)
            : classNames;
        const [isDragging, setIsDragging] = useState(false);
        const [filePreviews, setFilePreviews] = useState<FilePreview[]>([]);
        const [rejections, setRejections] = useState<FileRejection[]>([]);
        const [lightboxOpen, setLightboxOpen] = useState(false);
        const [lightboxIndex, setLightboxIndex] = useState(0);

        const inputRef = useRef<HTMLInputElement>(null);
        const dragDepth = useRef(0);
        const alive = useRef(true);

        // Always-current snapshot of previews so the unmount cleanup can revoke them.
        const previewsRef = useRef<FilePreview[]>([]);
        useEffect(() => {
            previewsRef.current = filePreviews;
        }, [filePreviews]);
        useEffect(
            () => () => {
                alive.current = false;
                previewsRef.current.forEach(revokePreview);
            },
            [],
        );

        // ── Imperative handle ────────────────────────────────────────────────────
        useImperativeHandle(
            ref,
            () => ({
                openFileBrowser: () => inputRef.current?.click(),
                clearFiles: () => {
                    setFilePreviews((prev) => {
                        prev.forEach(revokePreview);

                        return [];
                    });
                    setRejections([]);
                    onChange(null);
                },
            }),
            [onChange],
        );

        // ── Sync when value is cleared externally (form.reset() etc.) ────────────
        // Render-time reconciliation — React's recommended alternative to an
        // effect for "adjust state when a prop changes". When the controlled
        // value drops to empty we release the object URLs and clear the cards.
        // The follow-up setState re-renders before commit, so the revoked URLs
        // are never painted.
        const [prevValue, setPrevValue] = useState(value);

        if (value !== prevValue) {
            setPrevValue(value);

            if (!value && filePreviews.length) {
                filePreviews.forEach(revokePreview);
                setFilePreviews([]);
            }
        }

        // ── Lightbox slides (built only when lightbox=true) ───────────────────────
        const lightboxSlides = lightbox
            ? [
                  ...existingFiles
                      .filter(
                          (f) =>
                              f.mime_type.startsWith('image/') ||
                              f.mime_type.startsWith('video/'),
                      )
                      .map((f) =>
                          f.mime_type.startsWith('video/')
                              ? {
                                    type: 'video' as const,
                                    sources: [
                                        { src: f.url, type: f.mime_type },
                                    ],
                                }
                              : {
                                    src: f.url,
                                    alt:
                                        f.name ?? f.path.split('/').pop() ?? '',
                                },
                      ),
                  ...filePreviews
                      .filter((p) => p.type !== 'other')
                      .map((p) =>
                          p.type === 'video'
                              ? {
                                    type: 'video' as const,
                                    sources: [
                                        { src: p.preview, type: p.file.type },
                                    ],
                                }
                              : { src: p.preview, alt: p.file.name },
                      ),
              ]
            : [];

        const openLightbox = useCallback(
            (source: 'existing' | 'new', localIdx: number) => {
                if (!lightbox) {
                    return;
                } // guard — does nothing when disabled

                const existingMediaCount = existingFiles.filter(
                    (f) =>
                        f.mime_type.startsWith('image/') ||
                        f.mime_type.startsWith('video/'),
                ).length;
                setLightboxIndex(
                    source === 'existing'
                        ? localIdx
                        : existingMediaCount + localIdx,
                );
                setLightboxOpen(true);
            },
            [lightbox, existingFiles],
        );

        // ── File processing ──────────────────────────────────────────────────────
        const processFiles = useCallback(
            (raw: FileList | File[]) => {
                const incoming = Array.from(raw);
                const rejected: FileRejection[] = [];

                const currentFiles = Array.isArray(value)
                    ? value
                    : value
                      ? [value]
                      : [];
                // De-dup ONLY against files already chosen in this selection —
                // never against existing server files. Filenames are not unique
                // across users, and the backend stores each upload under its own
                // randomized path, so re-uploading a same-named file is valid.
                const seen = new Set<string>();

                if (dedupe) {
                    currentFiles.forEach((f) => seen.add(fileProgressKey(f)));
                }

                let valid: File[] = [];

                for (const file of incoming) {
                    if (dedupe && seen.has(fileProgressKey(file))) {
                        rejected.push({
                            file,
                            reason: 'duplicate',
                            message: `"${file.name}" is already selected.`,
                        });
                        continue;
                    }

                    if (!matchesAccept(file, accept)) {
                        rejected.push({
                            file,
                            reason: 'type',
                            message: `"${file.name}" is not an accepted file type.`,
                        });
                        continue;
                    }

                    if (file.size / BYTES_PER_MB > maxSize) {
                        rejected.push({
                            file,
                            reason: 'size',
                            message: `"${file.name}" exceeds the ${maxSize} MB limit.`,
                        });
                        continue;
                    }

                    seen.add(fileProgressKey(file));
                    valid.push(file);
                }

                // Enforce count limits.
                if (!multiple) {
                    valid = valid.slice(0, 1);
                } else if (maxFiles) {
                    const used = currentFiles.length + existingFiles.length;
                    const room = Math.max(0, maxFiles - used);

                    if (valid.length > room) {
                        valid.slice(room).forEach((file) =>
                            rejected.push({
                                file,
                                reason: 'count',
                                message: `Maximum ${maxFiles} files allowed — "${file.name}" was skipped.`,
                            }),
                        );
                        valid = valid.slice(0, room);
                    }
                }

                setRejections(rejected);

                if (rejected.length) {
                    onReject?.(rejected);
                }

                if (!valid.length) {
                    return;
                }

                const previews = valid.map(buildPreview);

                if (!alive.current) {
                    previews.forEach(revokePreview);

                    return;
                }

                if (multiple) {
                    setFilePreviews((prev) => [...prev, ...previews]);
                    onChange([...currentFiles, ...valid]);
                } else {
                    setFilePreviews((prev) => {
                        prev.forEach(revokePreview);

                        return previews;
                    });
                    onChange(valid[0]);
                }
            },
            [
                value,
                existingFiles,
                multiple,
                maxFiles,
                maxSize,
                accept,
                dedupe,
                onChange,
                onReject,
            ],
        );

        // ── DOM event handlers ───────────────────────────────────────────────────
        const handleChange = useCallback(
            (e: ChangeEvent<HTMLInputElement>) => {
                if (e.target.files?.length) {
                    processFiles(e.target.files);
                }

                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
            [processFiles],
        );

        const handleDragOver = useCallback((e: DragEvent<HTMLDivElement>) => {
            e.preventDefault();
            e.stopPropagation();
        }, []);

        const handleDragEnter = useCallback(
            (e: DragEvent<HTMLDivElement>) => {
                e.preventDefault();
                e.stopPropagation();

                if (disabled) {
                    return;
                }

                dragDepth.current += 1;
                setIsDragging(true);
            },
            [disabled],
        );

        const handleDragLeave = useCallback((e: DragEvent<HTMLDivElement>) => {
            e.preventDefault();
            e.stopPropagation();
            // Only un-highlight when the cursor truly leaves the zone — not when it
            // crosses into a child element (which also fires dragleave).
            dragDepth.current = Math.max(0, dragDepth.current - 1);

            if (dragDepth.current === 0) {
                setIsDragging(false);
            }
        }, []);

        const handleDrop = useCallback(
            (e: DragEvent<HTMLDivElement>) => {
                e.preventDefault();
                e.stopPropagation();
                dragDepth.current = 0;
                setIsDragging(false);

                if (!disabled && e.dataTransfer.files.length) {
                    processFiles(e.dataTransfer.files);
                }
            },
            [disabled, processFiles],
        );

        const handleRemoveNew = useCallback(
            (idx: number) => {
                setFilePreviews((prev) => {
                    const removed = prev[idx];

                    if (removed) {
                        revokePreview(removed);
                    }

                    return prev.filter((_, i) => i !== idx);
                });

                if (multiple) {
                    const current = Array.isArray(value) ? value : [];
                    const next = current.filter((_, i) => i !== idx);
                    onChange(next.length ? next : null);
                } else {
                    onChange(null);
                }
            },
            [multiple, value, onChange],
        );

        // ── Derived flags ────────────────────────────────────────────────────────
        const totalFiles = filePreviews.length + existingFiles.length;
        const canAddMore = multiple
            ? !maxFiles || totalFiles < maxFiles
            : totalFiles === 0;

        const hasPerFile = fileProgresses != null;
        const hasAggregate = uploadProgress != null;
        /** True when uploading but no numeric value → indeterminate ring. */
        const pulseLoading = isUploading && !hasPerFile && !hasAggregate;

        /**
         * Progress for a single card:
         *  per-file  → look up by stable identity ({@link fileProgressKey}),
         *              falling back to bare filename for legacy callers
         *  aggregate → same value for all cards
         *  none      → undefined (no overlay shown)
         */
        const resolveCardProgress = (file: File): number | undefined => {
            if (hasPerFile) {
                return (
                    fileProgresses![fileProgressKey(file)] ??
                    fileProgresses![file.name]
                );
            }

            if (hasAggregate) {
                return uploadProgress!;
            }

            return undefined;
        };

        const resolveCardLoading = (): boolean => pulseLoading;

        /** Cancel control on a card only makes sense in per-file mode while uploading. */
        const cardCancel =
            onCancel && isUploading && hasPerFile ? onCancel : undefined;

        // ── onPreview factory — returns undefined when lightbox is disabled ───────
        const makeOnPreview = (
            source: 'existing' | 'new',
            localIdx: number,
            type: 'image' | 'video' | 'other',
        ): (() => void) | undefined => {
            if (!lightbox || type === 'other') {
                return undefined;
            }

            return () => openLightbox(source, localIdx);
        };

        // ── Shared sub-elements ──────────────────────────────────────────────────

        /** Lightbox mounted only when the feature is enabled. */
        const LightboxEl = lightbox ? (
            <Lightbox
                open={lightboxOpen}
                close={() => setLightboxOpen(false)}
                slides={lightboxSlides}
                index={lightboxIndex}
                plugins={[Zoom, Video]}
                zoom={{ maxZoomPixelRatio: 4 }}
            />
        ) : null;

        const DropZoneEl = canAddMore && (
            <div
                role="button"
                tabIndex={disabled ? -1 : 0}
                aria-label="Upload area – click or drag files here"
                onDragOver={handleDragOver}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                onClick={() => !disabled && inputRef.current?.click()}
                onKeyDown={(e) => {
                    if ((e.key === 'Enter' || e.key === ' ') && !disabled) {
                        e.preventDefault();
                        inputRef.current?.click();
                    }
                }}
                className={cn(
                    isAvatar
                        ? [
                              'relative flex shrink-0 items-center justify-center',
                              'transition-all duration-200 select-none',
                              isDragging
                                  ? 'scale-[1.03] border-primary bg-primary/10'
                                  : '',
                              disabled &&
                                  'pointer-events-none cursor-not-allowed opacity-50',
                              error && 'border-destructive',
                          ]
                        : [
                              'flex flex-col items-center justify-center gap-2 px-6 py-10 text-center',
                              'rounded-xl border-2 border-dashed transition-all duration-200 select-none',
                              'hover:border-primary hover:bg-primary/5',
                              isDragging
                                  ? 'scale-[1.015] border-primary bg-primary/10'
                                  : 'border-border',
                              disabled &&
                                  'pointer-events-none cursor-not-allowed opacity-50',
                              error && 'border-destructive',
                          ],
                    mergedClassNames.dropzone,
                )}
                style={
                    isAvatar
                        ? { width: avatarSize, height: avatarSize }
                        : undefined
                }
            >
                <input
                    ref={inputRef}
                    type="file"
                    multiple={multiple}
                    accept={accept}
                    disabled={disabled}
                    required={required && totalFiles === 0}
                    onChange={handleChange}
                    className="sr-only"
                    tabIndex={-1}
                />
                <div
                    className={cn(
                        isAvatar
                            ? 'flex flex-col items-center gap-1'
                            : 'flex flex-col items-center gap-1.5',
                        mergedClassNames.dropzoneContent,
                    )}
                >
                    {isAvatar ? (
                        (avatarFallback ?? (
                            <Upload
                                className="size-5 text-muted-foreground"
                                aria-hidden="true"
                            />
                        ))
                    ) : (
                        <Upload
                            className="h-8 w-8 text-muted-foreground"
                            aria-hidden="true"
                        />
                    )}
                    {!isAvatar ? (
                        <>
                            <p className="text-sm font-medium text-foreground">
                                {placeholder}
                            </p>
                            {hint ? (
                                <p className="text-xs text-muted-foreground">
                                    {hint}
                                </p>
                            ) : null}
                            <p className="text-xs text-muted-foreground">
                                Max {maxSize} MB per file
                                {maxFiles && ` · up to ${maxFiles} files`}
                                {accept && ` · ${accept}`}
                            </p>
                        </>
                    ) : null}
                </div>
            </div>
        );

        const HiddenFileInputEl =
            !canAddMore && isAvatar ? (
                <input
                    ref={inputRef}
                    type="file"
                    multiple={multiple}
                    accept={accept}
                    disabled={disabled}
                    onChange={handleChange}
                    className="sr-only"
                    tabIndex={-1}
                />
            ) : null;

        const showAggregateRow = isUploading && !hasPerFile && totalFiles > 0;
        const AggregateRow = showAggregateRow && (
            <AggregateProgressRow
                value={hasAggregate ? uploadProgress! : 0}
                indeterminate={pulseLoading}
                onCancel={onCancel && isUploading ? onCancel : undefined}
                className={mergedClassNames.aggregateProgress}
            />
        );

        const RejectionsEl = showRejections && rejections.length > 0 && (
            <div className={cn('space-y-1', mergedClassNames.error)}>
                {rejections.map((r, i) => (
                    <p
                        key={`${r.file.name}-${i}`}
                        className="flex items-start gap-1.5 text-xs text-destructive"
                    >
                        <AlertCircle
                            className="mt-px h-3.5 w-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        <span>{r.message}</span>
                    </p>
                ))}
            </div>
        );

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // RENDER: Single mode — existing server file
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (
            !multiple &&
            existingFiles.length > 0 &&
            filePreviews.length === 0
        ) {
            const f = existingFiles[0];
            const ft = resolveFileType(f.mime_type);
            const displayName = f.name ?? f.path.split('/').pop() ?? 'File';

            return (
                <div
                    className={cn(
                        isAvatar ? mergedClassNames.wrapper : 'w-full',
                        className,
                        !isAvatar && mergedClassNames.wrapper,
                    )}
                >
                    {LightboxEl}
                    {HiddenFileInputEl}
                    <div
                        className={cn(
                            isAvatar ? 'flex items-center gap-4' : undefined,
                        )}
                    >
                        <div className={cn(isAvatar && 'relative shrink-0')}>
                            <FileCard
                                name={displayName}
                                size={f.size}
                                preview={f.url}
                                type={ft}
                                mime={f.mime_type}
                                compact={isAvatar}
                                fixedSize={isAvatar ? avatarSize : undefined}
                                onRemove={
                                    onRemoveExisting && !isUploading
                                        ? () => onRemoveExisting(f.id)
                                        : undefined
                                }
                                onPreview={
                                    isAvatar
                                        ? undefined
                                        : makeOnPreview('existing', 0, ft)
                                }
                                classNames={mergedClassNames}
                            />
                            {isAvatar && !isUploading ? (
                                <button
                                    type="button"
                                    aria-label="Change photo"
                                    onClick={() => inputRef.current?.click()}
                                    className="absolute inset-0 rounded-full bg-black/0 opacity-0 transition hover:bg-black/45 hover:opacity-100 focus-visible:opacity-100 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                >
                                    <span className="sr-only">
                                        Change photo
                                    </span>
                                </button>
                            ) : null}
                        </div>
                        {isAvatar ? (
                            <AvatarUploadCaption
                                title="Profile photo"
                                fileName={displayName}
                                fileSize={f.size}
                                description="Click the photo to replace, or remove it."
                            />
                        ) : null}
                    </div>
                    {error && (
                        <p
                            className={cn(
                                'mt-1.5 text-sm text-destructive',
                                mergedClassNames.error,
                            )}
                        >
                            {error}
                        </p>
                    )}
                </div>
            );
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // RENDER: Single mode — newly selected file
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (!multiple && filePreviews.length > 0) {
            const p = filePreviews[0];

            return (
                <div
                    className={cn(
                        isAvatar
                            ? cn(mergedClassNames.wrapper, 'space-y-2')
                            : 'w-full space-y-2',
                        className,
                        !isAvatar && mergedClassNames.wrapper,
                    )}
                >
                    {LightboxEl}
                    {HiddenFileInputEl}
                    <div
                        className={cn(
                            isAvatar ? 'flex items-center gap-4' : undefined,
                        )}
                    >
                        <div className={cn(isAvatar && 'relative shrink-0')}>
                            <FileCard
                                name={p.file.name}
                                size={p.file.size}
                                preview={p.preview}
                                type={p.type}
                                mime={p.file.type}
                                compact={isAvatar}
                                fixedSize={isAvatar ? avatarSize : undefined}
                                onRemove={
                                    !isUploading
                                        ? () => handleRemoveNew(0)
                                        : undefined
                                }
                                onCancel={cardCancel}
                                onPreview={
                                    isAvatar
                                        ? undefined
                                        : makeOnPreview('new', 0, p.type)
                                }
                                progress={resolveCardProgress(p.file)}
                                loading={resolveCardLoading()}
                                classNames={mergedClassNames}
                            />
                            {isAvatar && !isUploading ? (
                                <button
                                    type="button"
                                    aria-label="Change photo"
                                    onClick={() => inputRef.current?.click()}
                                    className="absolute inset-0 rounded-full bg-black/0 opacity-0 transition hover:bg-black/45 hover:opacity-100 focus-visible:opacity-100 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                >
                                    <span className="sr-only">
                                        Change photo
                                    </span>
                                </button>
                            ) : null}
                        </div>
                        {isAvatar ? (
                            <AvatarUploadCaption
                                title="Profile photo"
                                fileName={p.file.name}
                                fileSize={p.file.size}
                                description="Click the photo to replace, or remove it."
                            />
                        ) : null}
                    </div>
                    {AggregateRow}
                    {RejectionsEl}
                    {error && (
                        <p
                            className={cn(
                                'text-sm text-destructive',
                                mergedClassNames.error,
                            )}
                        >
                            {error}
                        </p>
                    )}
                </div>
            );
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // RENDER: Drop zone + multiple-file grid
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        return (
            <div
                className={cn(
                    isAvatar ? mergedClassNames.wrapper : 'w-full space-y-3',
                    className,
                    !isAvatar && mergedClassNames.wrapper,
                )}
            >
                {LightboxEl}

                {isAvatar ? (
                    <div className="flex flex-wrap items-start gap-4">
                        {DropZoneEl}
                        <AvatarUploadCaption
                            title={placeholder}
                            description={
                                hint
                                    ? `${hint} · Max ${maxSize} MB`
                                    : `Max ${maxSize} MB`
                            }
                        />
                    </div>
                ) : (
                    DropZoneEl
                )}

                {multiple && AggregateRow}

                {RejectionsEl}

                {!isAvatar && totalFiles > 0 && (
                    <div
                        className={cn(
                            'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4',
                            mergedClassNames.grid,
                        )}
                    >
                        {/* Existing server files */}
                        {existingFiles.map((f, idx) => {
                            const ft = resolveFileType(f.mime_type);

                            return (
                                <FileCard
                                    key={f.id}
                                    name={
                                        f.name ??
                                        f.path.split('/').pop() ??
                                        'File'
                                    }
                                    size={f.size}
                                    preview={f.url}
                                    type={ft}
                                    mime={f.mime_type}
                                    onRemove={
                                        onRemoveExisting && !isUploading
                                            ? () => onRemoveExisting(f.id)
                                            : undefined
                                    }
                                    onPreview={makeOnPreview(
                                        'existing',
                                        idx,
                                        ft,
                                    )}
                                    classNames={mergedClassNames}
                                />
                            );
                        })}

                        {/* Newly selected files */}
                        {filePreviews.map((p, idx) => {
                            const mediaIdx = filePreviews
                                .slice(0, idx)
                                .filter((x) => x.type !== 'other').length;

                            return (
                                <FileCard
                                    key={p.key}
                                    name={p.file.name}
                                    size={p.file.size}
                                    preview={p.preview}
                                    type={p.type}
                                    mime={p.file.type}
                                    onRemove={
                                        !isUploading
                                            ? () => handleRemoveNew(idx)
                                            : undefined
                                    }
                                    onCancel={cardCancel}
                                    onPreview={makeOnPreview(
                                        'new',
                                        mediaIdx,
                                        p.type,
                                    )}
                                    progress={resolveCardProgress(p.file)}
                                    loading={resolveCardLoading()}
                                    classNames={mergedClassNames}
                                />
                            );
                        })}
                    </div>
                )}

                {error && (
                    <p
                        className={cn(
                            'text-sm text-destructive',
                            mergedClassNames.error,
                        )}
                    >
                        {error}
                    </p>
                )}
            </div>
        );
    },
);

FileUpload.displayName = 'FileUpload';
export default FileUpload;
